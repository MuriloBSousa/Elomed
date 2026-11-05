<?php
class IAController {
    private $iaModel;
    private $protocoloModel;

    public function __construct() {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        // Business rule: only users with the clinical AI permission may use the AI.
        if (!RBAC::can(Constants::PERM_DIAGNOSTICO_IA)) {
            header('Location: ' . BASE_URL . '/painel/index');
            exit;
        }

        $this->iaModel = new IA();
        $this->protocoloModel = new Protocolo();
    }

    /**
     * Processes the anamnesis and calls the AI API to generate insights
     */
    public function gerar_diagnostico() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/painel/index');
            exit;
        }

        // Business rule: every AI query requires a valid CSRF token
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Token de segurança inválido');
            header('Location: ' . BASE_URL . '/medico/index');
            exit;
        }

        // Business rule: the schedule must belong to the logged professional
        // (or an administrator), otherwise a forged id_agendamento would expose
        // another patient's consultation to any professional.
        $idAgendamentoChk = (int) ($_POST['id_agendamento'] ?? 0);
        if ($idAgendamentoChk > 0 && !$this->podeAgirNoAgendamento($idAgendamentoChk)) {
            Session::flash('error', 'Este agendamento pertence a outro profissional.');
            header('Location: ' . BASE_URL . '/medico/index');
            exit;
        }

        // Daily AI query rate-limit gate
        $dailyLimit = 5;
        $usosHoje = $this->iaModel->contarUsosHoje(Session::get('usuario_id'));
        if ($usosHoje >= $dailyLimit) {
            Session::flash('error', "Limite de consultas de IA do dia atingido ({$dailyLimit}/dia). Tente novamente amanha.");
            header('Location: ' . BASE_URL . '/medico/index');
            exit;
        }

        // Collect form data
        $id_agendamento = $_POST['id_agendamento'] ?? null;
        $protocolo_id = $_POST['protocolo_id'] ?? null;
        $queixa = $_POST['queixa_principal'] ?? '';
        $tempo = $_POST['tempo_sintomas'] ?? '';
        $frequencia = $_POST['frequencia_sintomas'] ?? '';
        $alergia = ($_POST['tem_alergia'] ?? 'nao') === 'sim' ? ($_POST['quais_alergias'] ?? 'Sim (não especificado)') : 'Nenhuma';
        $historico = $_POST['historico_medico'] ?? '';

        // Collect vital signs
        $pa = $_POST['pa'] ?? '';
        $fc = $_POST['fc'] ?? '';
        $temperatura = $_POST['temperatura'] ?? '';
        $glicemia = $_POST['glicemia'] ?? '';

        // Collect protocol question responses
        $respostas_protocolo = [];
        if (!empty($_POST)) {
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'pergunta_') === 0) {
                    $questionId = str_replace('pergunta_', '', $key);
                    $respostas_protocolo[$questionId] = $value;
                }
            }
        }

        // Fetch protocol questions for mapping
        $perguntas_protocolo = [];
        $protocolo_info = '';
        if ($protocolo_id) {
            $protocolo = $this->protocoloModel->getById($protocolo_id);
            if ($protocolo) {
                $protocolo_info = "- Protocolo Selecionado: {$protocolo['nome']} ({$protocolo['categoria']})\n";
                $perguntas_protocolo = $this->protocoloModel->getPerguntas($protocolo_id);
                
                // Append protocol responses to the prompt
                if (!empty($respostas_protocolo)) {
                    $protocolo_info .= "\nRespostas do Protocolo:\n";
                    foreach ($perguntas_protocolo as $pergunta) {
                        $questionId = $pergunta['id'];
                        if (isset($respostas_protocolo[$questionId])) {
                            $protocolo_info .= "- {$pergunta['pergunta']}: {$respostas_protocolo[$questionId]}\n";
                        }
                    }
                }
            }
        }

        // Build the structured prompt for the AI
        $prompt = "Você é um assistente clínico especialista de suporte à decisão médica. Analise o seguinte cenário clínico:\n\n";
        $prompt .= "- Queixa Principal: {$queixa}\n";
        $prompt .= "- Tempo dos Sintomas: {$tempo}\n";
        $prompt .= "- Padrão dos Sintomas: {$frequencia}\n";
        $prompt .= "- Alergias relatadas: {$alergia}\n";
        $prompt .= "- Histórico do Paciente: {$historico}\n";
        $prompt .= "- Sinais Vitais: PA={$pa}, FC={$fc}, Temp={$temperatura}, Glicemia={$glicemia}\n";
        if ($protocolo_info) {
            $prompt .= "\n" . $protocolo_info;
        }
        $prompt .= "\nForneça um JSON válido contendo exatamente três chaves com valores STRING (não objetos):\n";
        $prompt .= "1. 'correlacao': Texto simples com análise do diagnóstico e diagnósticos diferenciais.\n";
        $prompt .= "2. 'alerta': Texto simples com riscos e contraindicações baseadas no histórico.\n";
        $prompt .= "3. 'recomendacoes': Texto simples com condutas complementares e orientações de suporte.\n";
        $prompt .= "CRÍTICO: Cada valor deve ser uma STRING simples, não um objeto ou array. Não use formatações markdown, retorne apenas o objeto JSON puro.";

        try {
            // Business rule: use the configured API key (Groq > OpenAI > .env).
            // IA::getApiKey() returns ['key','provider','model'] already resolved.
            $apiConfig = $this->iaModel->getApiKey();

            $responseIa = $this->iaModel->chamarApi($apiConfig, $prompt);

            // Decode the AI response
            $insights = json_decode($responseIa, true);

            // If the AI fails or does not return the expected JSON, provide default values
            if (!$insights || json_last_error() !== JSON_ERROR_NONE || !isset($insights['correlacao'])) {
                Logger::warning("IA response invalid or failed", [
                    'id_agendamento' => $id_agendamento,
                    'json_error' => json_last_error_msg()
                ]);
                
                $insights = [
                    'correlacao' => 'Não foi possível gerar a correlação automática neste momento. Reveja os dados informados.',
                    'alerta' => 'Falha ao analisar alertas farmacológicos de segurança. Verifique manualmente.',
                    'recomendacoes' => 'Prossiga com a avaliação clínica convencional e documente no prontuário.'
                ];
            }
            
            Logger::audit(Constants::AUDITORIA_ACAO_USO_IA, [
                'id_agendamento' => $id_agendamento,
                'protocolo_id' => $protocolo_id
            ]);

            (new Auditoria())->registrar(
                Constants::AUDITORIA_ACAO_USO_IA,
                'consulta de IA gerada',
                ['id_agendamento' => $id_agendamento]
            );

            $this->iaModel->registrarUso(Session::get('usuario_id'));
        } catch (Exception $e) {
            Logger::error("Error calling IA API", [
                'error' => $e->getMessage(),
                'id_agendamento' => $id_agendamento
            ]);
            
            $insights = [
                'correlacao' => 'Erro ao conectar com o serviço de IA. Verifique a configuração.',
                'alerta' => 'Serviço indisponível no momento.',
                'recomendacoes' => 'Prossiga com a avaliação clínica convencional e documente no prontuário.'
            ];
        }

        // Return the processed data
        return [
            'id_agendamento' => $id_agendamento,
            'queixa' => $queixa,
            'tempo' => $tempo,
            'frequencia' => $frequencia,
            'alergia' => $alergia,
            'historico' => $historico,
            'pa' => $pa,
            'fc' => $fc,
            'temperatura' => $temperatura,
            'glicemia' => $glicemia,
            'protocolo_id' => $protocolo_id,
            'respostas_protocolo' => $respostas_protocolo,
            'perguntas_protocolo' => $perguntas_protocolo,
            'insights' => $insights,
            'modelo_ia' => Env::get('IA_PROVIDER', 'groq'),
            'horario_ia' => date('H:i:s d/m/Y')
        ];
    }

    /**
     * Business rule: only the professional who owns the schedule (or an
     * administrator) may run AI analysis over that visit's data.
     */
    private function podeAgirNoAgendamento($idAgendamento): bool {
        if (Session::get('usuario_perfil') === Constants::PERFIL_ADMIN) {
            return true;
        }
        $profissional = (new Profissional())->getIdByUsuario((int) Session::get('usuario_id'));
        if (!$profissional) {
            return false;
        }
        return (new Agendamento())->belongsToProfissional((int) $idAgendamento, (int) $profissional);
    }
}
