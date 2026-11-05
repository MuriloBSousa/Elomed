<?php


class IA extends Model {
    
    /**
     * Returns the API key (Groq or OpenAI)
     * Prioritizes Groq (free), then OpenAI, or uses environment variable
     */
    public function getApiKey() {
        // Try loading Groq key (priority - free)
        $groqConfigFile = __DIR__ . '/../../config/groq_api_key.php';
        if (file_exists($groqConfigFile)) {
            $config = require $groqConfigFile;
            if (!empty($config['groq_api_key'])) {
                return ['key' => $config['groq_api_key'], 'provider' => 'groq', 'model' => $config['model'] ?? 'openai/gpt-oss-20b'];
            }
        }
        
        // Try using Groq environment variable
        $envGroqKey = getenv('GROQ_API_KEY');
        if (!empty($envGroqKey)) {
            return ['key' => $envGroqKey, 'provider' => 'groq', 'model' => getenv('GROQ_MODEL') ?: 'openai/gpt-oss-20b'];
        }

        // Fallback to OpenAI (if configured)
        $openaiConfigFile = __DIR__ . '/../../config/api_key.php';
        if (file_exists($openaiConfigFile)) {
            $config = require $openaiConfigFile;
            if (!empty($config['openai_api_key'])) {
                return ['key' => $config['openai_api_key'], 'provider' => 'openai', 'model' => 'gpt-4o-mini'];
            }
        }
        
        // Try using OpenAI environment variable
        $envOpenaiKey = getenv('OPENAI_API_KEY');
        if (!empty($envOpenaiKey)) {
            return ['key' => $envOpenaiKey, 'provider' => 'openai', 'model' => 'gpt-4o-mini'];
        }
        
        // If not found, return placeholder and the system will use fallback
        return ['key' => '', 'provider' => 'none', 'model' => ''];
    }

    /**
     * Calls the AI API (Groq or OpenAI)
     * $timeoutSegundos keeps the preliminary triage assessment from holding the
     * patient's request for the full default timeout.
     */
    public function chamarApi($apiConfig, $prompt, $timeoutSegundos = 30) {
        // If no API key configured, return simulation for demonstration
        if (empty($apiConfig['key'])) {
            return $this->simularResposta($prompt);
        }

        $provider = $apiConfig['provider'];
        $apiKey = $apiConfig['key'];
        $model = $apiConfig['model'];

        // Configure URL and headers based on provider
        if ($provider === 'groq') {
            $url = 'https://api.groq.com/openai/v1/chat/completions';
        } else {
            $url = 'https://api.openai.com/v1/chat/completions';
        }

        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.2
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSegundos);

        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result && $httpCode === 200) {
            $jsonRes = json_decode($result, true);
            return $jsonRes['choices'][0]['message']['content'] ?? '';
        }

        // If API fails, log and use simulation to avoid blocking the flow
        Logger::warning("IA API falhou, usando simulação", [
            'provider' => $provider,
            'http_code' => $httpCode,
            'curl_error' => $curlError
        ]);
        return $this->simularResposta($prompt);
    }

    /**
     * Generates the AI recommendation for triage/encaminhamento.
     * Returns the risk classification (pulseira), the recommended professional
     * and the justification for both, based on the patient's data.
     */
    public function analisarEncaminhamento($dados) {
        $solicitante = trim($dados['solicitante'] ?? '');
        $classificacaoInformada = trim($dados['classificacao_solicitante'] ?? '');

        $defaultClassificacao = in_array($classificacaoInformada, ['vermelho', 'amarelo', 'verde'], true)
            ? $classificacaoInformada : 'verde';

        $prompt = "Você é um assistente clínico de apoio à triagem de atendimento domiciliar. ";
        $prompt .= "Analise o cenário e recomende a classificação de risco (pulseira) e o profissional mais adequado para realizar a visita.\n\n";
        $prompt .= "- Nome do solicitante: " . ($solicitante !== '' ? $solicitante : 'Não informado') . "\n";
        $prompt .= "- Classificação informada pelo solicitante: " . ($classificacaoInformada !== '' ? $classificacaoInformada : 'não informada') . "\n";
        $prompt .= "- Idade/sexo do paciente: " . ($dados['idade_sexo'] ?? 'não informado') . "\n";
        $prompt .= "- Alergias relatadas: " . ((trim($dados['alergias'] ?? '') !== '') ? $dados['alergias'] : 'Nenhuma') . "\n";
        $prompt .= "- Doenças prévias: " . ((trim($dados['doencas_previas'] ?? '') !== '') ? $dados['doencas_previas'] : 'Nenhuma') . "\n";
        $prompt .= "- Medicamentos em uso: " . ((trim($dados['medicamentos_uso'] ?? '') !== '') ? $dados['medicamentos_uso'] : 'Nenhum') . "\n";
        $prompt .= "- Cirurgias: " . ((trim($dados['cirurgias'] ?? '') !== '') ? $dados['cirurgias'] : 'Nenhuma') . "\n";
        $prompt .= "\nForneça um JSON válido com exatamente quatro chaves:\n";
        $prompt .= "1. 'classificacao': 'verde' (comum) ou 'amarelo' (prioridade) ou 'vermelho' (urgente).\n";
        $prompt .= "2. 'justificativa_classificacao': string com o motivo clínico da classificação.\n";
        $prompt .= "3. 'profissional_indicado': 'medico', 'enfermeiro' ou 'tecnico_enfermagem'.\n";
        $prompt .= "4. 'justificativa_profissional': string explicando por que esse profissional é o indicado para esse paciente.\n";
        $prompt .= "CRÍTICO: Retorne apenas o objeto JSON puro, sem markdown e sem texto extra.";

        try {
            $apiConfig = $this->getApiKey();
            $resposta = $this->chamarApi($apiConfig, $prompt);

            $data = json_decode($resposta, true);

            if (!is_array($data) || !isset($data['classificacao'])) {
                Logger::warning("IA encaminhamento: resposta inválida, aplicando padrão", [
                    'raw' => substr((string) $resposta, 0, 300)
                ]);
                $data = [];
            }

            $classificacao = in_array($data['classificacao'] ?? '', ['vermelho', 'amarelo', 'verde'], true)
                ? $data['classificacao'] : $defaultClassificacao;
            $profissional = in_array($data['profissional_indicado'] ?? '', ['medico', 'enfermeiro', 'tecnico_enfermagem'], true)
                ? $data['profissional_indicado'] : 'enfermeiro';

            return [
                'classificacao' => $classificacao,
                'justificativa_classificacao' => trim((string) ($data['justificativa_classificacao'] ?? '')),
                'profissional_indicado' => $profissional,
                'justificativa_profissional' => trim((string) ($data['justificativa_profissional'] ?? '')),
                'modelo' => $apiConfig['model'] !== '' ? $apiConfig['model'] : 'simulacao'
            ];
        } catch (Exception $e) {
            Logger::error("IA encaminhamento: erro na chamada da API", ['error' => $e->getMessage()]);
            return [
                'classificacao' => $defaultClassificacao,
                'justificativa_classificacao' => 'Não foi possível gerar a justificativa automática.',
                'profissional_indicado' => 'enfermeiro',
                'justificativa_profissional' => 'Não foi possível gerar a recomendação automática.',
                'modelo' => 'fallback'
            ];
        }
    }

    /**
     * Generates the AI analysis for the NURSING visit form (Enfermeiro/Técnico em
     * Enfermagem). This is intentionally distinct from analisarEncaminhamento
     * (which targets the medico): the prompt focuses on nursing assessment,
     * vital signs, nursing observations and care plan/recommendations, NOT on
     * triage encaminhamento. Returns a JSON with:
     *   - 'avaliacao_enfermagem': nurse's clinical appraisal of the visit.
     *   - 'resumo_sinais_vitais': concise summary of the recorded vital signs.
     *   - 'cuidados_recomendados': recommended nursing care/condutas.
     *   - 'orientacoes_paciente': guidance to give the patient/caregiver.
     *   - 'sugestao_duvida': boolean hint indicating when a medical escalation
     *     (autenticacao) is recommended because the case is outside the
     *     nursing scope.
     * Also returns 'modelo' (model used or 'simulacao').
     */
    public function analisarVisitaEnfermagem($dados) {
        $queixas = trim($dados['queixas_observadas'] ?? '');
        $sinais = trim($dados['sinais_vitais'] ?? '');
        $observacoes = trim($dados['observacoes_enfermagem'] ?? '');
        $condicao = trim($dados['estado_geral'] ?? '');

        $defaultModelo = 'simulacao';

        $prompt = "Você é um assistente clínico de apoio à enfermagem em atendimento domiciliar. ";
        $prompt .= "Analise os dados registrados pelo(a) enfermeiro(a)/técnico(a) de enfermagem durante a visita e gere as recomendações de cuidado de enfermagem.\n\n";
        $prompt .= "- Queixas observadas pelo profissional: " . ((trim($queixas) !== '') ? $queixas : 'nenhuma queixa específica registrada') . "\n";
        $prompt .= "- Sinais vitais aferidos: " . ((trim($sinais) !== '') ? $sinais : 'não informados') . "\n";
        $prompt .= "- Observações de enfermagem (aspectos da visita): " . ((trim($observacoes) !== '') ? $observacoes : 'nenhuma observação registrada') . "\n";
        $prompt .= "- Estado geral do paciente: " . ((trim($condicao) !== '') ? $condicao : 'não informado') . "\n";
        $prompt .= "\nForneça um JSON válido com exatamente cinco chaves:\n";
        $prompt .= "1. 'avaliacao_enfermagem': string com a avaliação da enfermagem sobre o quadro apresentado na visita.\n";
        $prompt .= "2. 'resumo_sinais_vitais': string resumindo os sinais vitais registrados.\n";
        $prompt .= "3. 'cuidados_recomendados': string com os cuidados/condutas de enfermagem recomendados.\n";
        $prompt .= "4. 'orientacoes_paciente': string com orientações para o paciente/cuidador.\n";
        $prompt .= "5. 'sugestao_duvida': booleano (true/false) indicando se o caso foge do escopo da enfermagem e merece avaliação/dúvida médica (autenticacao pelo médico).\n";
        $prompt .= "CRÍTICO: Retorne apenas o objeto JSON puro, sem markdown e sem texto extra.";

        try {
            $apiConfig = $this->getApiKey();
            $resposta = $this->chamarApi($apiConfig, $prompt);

            $data = json_decode($resposta, true);

            if (!is_array($data) || !isset($data['avaliacao_enfermagem'])) {
                Logger::warning("IA visita enfermagem: resposta inválida, aplicando padrão", [
                    'raw' => substr((string) $resposta, 0, 300)
                ]);
                $data = [];
            }

            return [
                'avaliacao_enfermagem' => trim((string) ($data['avaliacao_enfermagem'] ?? 'Avaliação de enfermagem não informada.')),
                'resumo_sinais_vitais' => trim((string) ($data['resumo_sinais_vitais'] ?? '')),
                'cuidados_recomendados' => trim((string) ($data['cuidados_recomendados'] ?? '')),
                'orientacoes_paciente' => trim((string) ($data['orientacoes_paciente'] ?? '')),
                'sugestao_duvida' => (bool) ($data['sugestao_duvida'] ?? false),
                'modelo' => $apiConfig['model'] !== '' ? $apiConfig['model'] : $defaultModelo
            ];
        } catch (Exception $e) {
            Logger::error("IA visita enfermagem: erro na chamada da API", ['error' => $e->getMessage()]);
            return $this->simularVisitaEnfermagem($prompt);
        }
    }
    private function simularVisitaEnfermagem($prompt) {
        $queixas = '';
        if (preg_match('/Queixas observadas pelo profissional:\s*(.+)/', $prompt, $matches)) {
            $queixas = trim($matches[1]);
        }
        $sinaisInfo = '';
        if (preg_match('/Sinais vitais aferidos:\s*(.+)/', $prompt, $matches)) {
            $sinaisInfo = trim($matches[1]);
        }
        $estado = '';
        if (preg_match('/Estado geral do paciente:\s*(.+)/', $prompt, $matches)) {
            $estado = trim($matches[1]);
        }

        $dadosVisita = trim($sinaisInfo);
        if ($dadosVisita === '' || $dadosVisita === 'não informados') {
            $sinaisResumo = 'sem registros de sinais vitais';
            $avaliacao = "Visita de enfermagem realizada. Sem sinais vitais registrados; avaliar sinais vitais e observar evolução do quadro. Estudo clínico: {$estado}, queixa geral de {$queixas}.";
            $cuidados = "1. Registrar sinais vitais na próxima aferição\n2. Orientar paciente/cuidador sobre sinais de alerta\n3. Reavaliação conforme protocolo de enfermagem";
        } else {
            $sinaisResumo = 'PA e FC aferidos: ' . ($sinaisInfo);
            $avaliacao = "Avaliação de enfermagem: paciente em acompanhamento domiciliar; sinais vitais aferidos ({$sinaisInfo}), estado geral: {$estado}. Mantém acompanhamento, sem sinais de alerta imediatos. ";
            $cuidados = "1. Monitoramento dos sinais vitais conforme rotina\n2. Reforçar orientações de autocuidado\n3. Manter registro e comunicar evolução.";
        }

        $orientacoes = "Orientar paciente e cuidador sobre sinais de alarme e contato do serviço em caso de piora do quadro.";

        $resposta = [
            'avaliacao_enfermagem' => trim($avaliacao),
            'resumo_sinais_vitais' => $sinaisResumo,
            'cuidados_recomendados' => trim($cuidados),
            'orientacoes_paciente' => trim($orientacoes),
            'sugestao_duvida' => (stripos($estado . $queixas, 'urgência') !== false || stripos($estado . $queixas, 'piora') !== false || stripos($queixas, 'dor intensa') !== false),
        ];
        return json_encode($resposta);
    }
    private function simularResposta($prompt) {
        // Preliminary triage assessment (roster-aware). Runs before the legacy
        // "pulseira" branch, since both prompts mention the classification.
        if (strpos($prompt, 'EQUIPE DISPON') !== false) {
            return $this->simularTriagemComEquipe($prompt);
        }

        // Contextual simulation for triage/encaminhamento prompts
        if (strpos($prompt, 'pulseira') !== false) {
            $classificacao = 'verde';
            if (preg_match('/Classificação informada pelo solicitante:\s*([^\n]+)/', $prompt, $m)) {
                $informada = strtolower(trim($m[1]));
                if (in_array($informada, ['vermelho', 'amarelo', 'verde'], true)) {
                    $classificacao = $informada;
                }
            }
            $profissional = $classificacao === 'vermelho' ? 'medico' : 'enfermeiro';
            return json_encode([
                'classificacao' => $classificacao,
                'justificativa_classificacao' => "Análise automática (simulação) com base nos dados cadastrais e na urgência informada. Risco considerado: {$classificacao}.",
                'profissional_indicado' => $profissional,
                'justificativa_profissional' => "Paciente sem sinais de instabilidade; o perfil {$profissional} é suficiente para a primeira visita, com retaguarda médica se necessário."
            ]);
        }

        // Extract basic information from the prompt to generate contextual response
        $chiefComplaint = '';
        if (preg_match('/Queixa Principal:\s*(.+)/', $prompt, $matches)) {
            $chiefComplaint = $matches[1];
        }

        $allergy = 'Nenhuma';
        if (preg_match('/Alergias relatadas:\s*(.+)/', $prompt, $matches)) {
            $allergy = $matches[1];
        }

        $pa = '';
        if (preg_match('/PA=([^,]+)/', $prompt, $matches)) {
            $pa = $matches[1];
        }

        $fc = '';
        if (preg_match('/FC=([^,]+)/', $prompt, $matches)) {
            $fc = $matches[1];
        }

        // Generate simulated response based on extracted data
        $response = [
            'correlacao' => "Análise clínica da queixa '{$chiefComplaint}': Considerando os sinais vitais (PA: {$pa}, FC: {$fc}) e histórico de alergias ({$allergy}), recomenda-se investigação detalhada. Possíveis diagnósticos diferenciais devem ser considerados conforme evolução do quadro.",
            'alerta' => "Atenção: Verificar contraindicações medicamentosas considerando histórico de alergias ({$allergy}). Monitorar sinais vitais de perto. Se houver piora, considerar encaminhamento para urgência.",
            'recomendacoes' => "1. Monitoramento contínuo dos sinais vitais\n2. Documentar evolução dos sintomas\n3. Considerar exames complementares conforme necessidade\n4. Reavaliação em 24-48 horas ou antes se houver piora\n5. Orientar paciente sobre sinais de alerta"
        ];

        return json_encode($response);
    }

    /**
     * Runs the preliminary assessment made the moment the patient requests the
     * triage. Unlike analisarEncaminhamento (kept for the older flow), this one
     * receives the ACTUAL roster of professionals and must name who should
     * receive the visit, taking specialty and time of experience into account.
     *
     * $dados    -> clinical context (complaint, symptoms, anamnesis...).
     * $candidatos -> rows from Profissional::listarParaEncaminhamento(), already
     *                ordered by the deterministic score (specialty + experience + load).
     *
     * Always returns a usable recommendation: when the API fails or returns
     * something invalid, it falls back to the top-ranked candidate so the
     * técnico never faces an empty recommendation.
     */
    public function avaliarTriagemComProfissionais(array $dados, array $candidatos) {
        $medicos = array_values(array_filter($candidatos, static function ($c) {
            return $c['perfil'] === 'medico';
        }));
        $enfermeiros = array_values(array_filter($candidatos, static function ($c) {
            return $c['perfil'] === 'enfermeiro';
        }));

        $fallback = [
            'classificacao' => 'verde',
            'justificativa_classificacao' => 'Classificação inicial padrão; confirme manualmente.',
            'perfil_recomendado' => !empty($medicos) ? 'medico' : 'enfermeiro',
            'justificativa_perfil' => 'Recomendação inicial por ausência de análise automática.',
            'profissional_sugerido' => null,
            'justificativa_profissional' => 'Não foi possível gerar a recomendação automática.',
            'modelo' => 'fallback',
            'sucesso' => false
        ];

        if (empty($candidatos)) {
            $fallback['justificativa_perfil'] = 'Nenhum profissional ativo com o perfil necessário.';
            return $fallback;
        }

        $listaTexto = $this->montarListaCandidatos($candidatos);

        $prompt  = "Você é um assistente clínico de apoio à triagem de atendimento domiciliar. ";
        $prompt .= "Uma triagem foi solicitada e um técnico de enfermagem irá revisar e decidir o encaminhamento final.\n\n";
        $prompt .= "DADOS DO CASO:\n";
        $prompt .= "- Solicitante: " . ($this->textoOuPadrao($dados['nome_solicitante'] ?? '', 'não informado')) . "\n";
        $prompt .= "- Idade/sexo: " . ($this->textoOuPadrao($dados['idade_sexo'] ?? '', 'não informado')) . "\n";
        $prompt .= "- Queixa principal: " . ($this->textoOuPadrao($dados['queixa_principal'] ?? '', 'não informada')) . "\n";
        $prompt .= "- Descrição dos sintomas: " . ($this->textoOuPadrao($dados['descricao_sintomas'] ?? '', 'não informada')) . "\n";
        $prompt .= "- Início dos sintomas: " . ($this->textoOuPadrao($dados['data_inicio_sintomas'] ?? '', 'não informado')) . "\n";
        $prompt .= "- Alergias: " . ($this->textoOuPadrao($dados['alergias'] ?? '', 'nenhuma')) . "\n";
        $prompt .= "- Doenças prévias: " . ($this->textoOuPadrao($dados['doencas_previas'] ?? '', 'nenhuma')) . "\n";
        $prompt .= "- Medicamentos em uso: " . ($this->textoOuPadrao($dados['medicamentos_uso'] ?? '', 'nenhum')) . "\n";
        $prompt .= "- Cirurgias anteriores: " . ($this->textoOuPadrao($dados['cirurgias'] ?? '', 'nenhuma')) . "\n";
        $prompt .= "\nEQUIPE DISPONÍVEL (já vem ordenada por aderência à especialidade, tempo de experiência e carga do dia):\n";
        $prompt .= $listaTexto;
        $prompt .= "\nINSTRUÇÕES:\n";
        $prompt .= "1. Escolha a classificação de risco (pulseira): 'verde' (comum), 'amarelo' (prioridade) ou 'vermelho' (urgente).\n";
        $prompt .= "2. Escolha o perfil de quem deve realizar a visita: 'medico' ou 'enfermeiro'.\n";
        $prompt .= "3. Escolha o profissional específico que melhor atende o caso, considerando a especialidade e o tempo de experiência informados acima.\n";
        $prompt .= "O id do profissional deve ser copiado exatamente da lista. Nunca invente um id.\n\n";
        $prompt .= "Responda APENAS com um JSON válido e sem markdown, com exatamente estas chaves:\n";
        $prompt .= "{\n";
        $prompt .= "  \"classificacao\": \"verde|amarelo|vermelho\",\n";
        $prompt .= "  \"justificativa_classificacao\": \"motivo clínico da classificação\",\n";
        $prompt .= "  \"perfil_recomendado\": \"medico|enfermeiro\",\n";
        $prompt .= "  \"justificativa_perfil\": \"por que este perfil e não o outro\",\n";
        $prompt .= "  \"profissional_sugerido_id\": 0,\n";
        $prompt .= "  \"justificativa_profissional\": \"por que este profissional, citando especialidade e experiência\"\n";
        $prompt .= "}";

        try {
            $apiConfig = $this->getApiKey();
            $resposta = $this->chamarApi($apiConfig, $prompt, 10);

            $data = json_decode($resposta, true);

            if (!is_array($data)) {
                Logger::warning('IA triagem: resposta inválida da API, usando fallback', [
                    'raw' => substr((string) $resposta, 0, 300)
                ]);
                return $fallback;
            }

            $classificacao = in_array($data['classificacao'] ?? '', ['vermelho', 'amarelo', 'verde'], true)
                ? $data['classificacao']
                : $fallback['classificacao'];

            $perfil = in_array($data['perfil_recomendado'] ?? '', ['medico', 'enfermeiro'], true)
                ? $data['perfil_recomendado']
                : $fallback['perfil_recomendado'];

            $sugerido = $this->validarProfissionalSugerido(
                $data['profissional_sugerido_id'] ?? null,
                $perfil,
                $candidatos
            );

            return [
                'classificacao' => $classificacao,
                'justificativa_classificacao' => $this->limitar($data['justificativa_classificacao'] ?? '', 500)
                    ?: $fallback['justificativa_classificacao'],
                'perfil_recomendado' => $perfil,
                'justificativa_perfil' => $this->limitar($data['justificativa_perfil'] ?? '', 500)
                    ?: $fallback['justificativa_perfil'],
                'profissional_sugerido' => $sugerido['id'],
                'justificativa_profissional' => $this->limitar($data['justificativa_profissional'] ?? '', 500)
                    ?: $fallback['justificativa_profissional'],
                'modelo' => $apiConfig['model'] !== '' ? $apiConfig['model'] : 'simulacao',
                'sucesso' => true
            ];
        } catch (Exception $e) {
            Logger::error('IA triagem: erro na chamada da API', ['error' => $e->getMessage()]);
            return $fallback;
        }
    }

    /**
     * Builds the numbered roster sent to the AI.
     */
    private function montarListaCandidatos(array $candidatos) {
        $linhas = [];
        foreach ($candidatos as $c) {
            $perfil = $c['perfil'] === 'medico' ? 'Médico(a)' : 'Enfermeiro(a)';
            $especialidade = $c['especialidade'] !== '' ? $c['especialidade'] : 'não informada';
            $linhas[] = sprintf(
                '- id=%d | %s | %s | especialidade: %s | experiência: %d ano(s) | visitas hoje: %d de %d',
                (int) $c['id_profissional'],
                $c['nome_profissional'],
                $perfil,
                $especialidade,
                (int) $c['tempo_experiencia_anos'],
                (int) $c['carga_hoje'],
                (int) $c['limite_diario']
            );
        }
        return implode("\n", $linhas) . "\n";
    }

    /**
     * The AI may answer with an id that does not exist or that belongs to the
     * other profile. In that case we fall back to the best candidate of the
     * chosen profile, so the recommendation is never inconsistent.
     */
    private function validarProfissionalSugerido($idSugerido, string $perfil, array $candidatos) {
        $doPerfil = array_values(array_filter($candidatos, static function ($c) use ($perfil) {
            return $c['perfil'] === $perfil;
        }));

        if (empty($doPerfil)) {
            $doPerfil = $candidatos;
        }

        $id = (int) $idSugerido;
        foreach ($doPerfil as $c) {
            if ((int) $c['id_profissional'] === $id && $id > 0) {
                return ['id' => $id, 'nome' => $c['nome_profissional']];
            }
        }

        $primeiro = reset($doPerfil);
        return ['id' => (int) $primeiro['id_profissional'], 'nome' => $primeiro['nome_profissional']];
    }

    private function textoOuPadrao($valor, $padrao) {
        $valor = trim((string) $valor);
        return $valor !== '' ? $valor : $padrao;
    }

    private function limitar($texto, $maximo) {
        return mb_substr(trim((string) $texto), 0, $maximo);
    }

    /**
     * Offline simulation for the roster-aware triage prompt. It picks the first
     * professional of the chosen profile from the list the caller sent, so the
     * demo behaves consistently even without an API key.
     */
    private function simularTriagemComEquipe($prompt) {
        $queixa = '';
        if (preg_match('/Queixa principal:\s*(.+)/', $prompt, $m)) {
            $queixa = trim($m[1]);
        }

        // Parses the roster lines: "- id=1 | Nome | Médico(a) | especialidade: X | ..."
        $candidatos = [];
        if (preg_match_all('/- id=(\d+) \| ([^|]+) \| (Médico\(a\)|Enfermeiro\(a\)) \| especialidade: ([^|]+) \| experi[^:]*: (\d+)/u', $prompt, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $linha) {
                $candidatos[] = [
                    'id' => (int) $linha[1],
                    'perfil' => $linha[3] === 'Médico(a)' ? 'medico' : 'enfermeiro',
                    'especialidade' => trim($linha[4]),
                    'experiencia' => (int) $linha[5]
                ];
            }
        }

        $texto = mb_strtolower($queixa);
        $urgente = (bool) preg_match('/urgente|grave|parada cardi|perda de consci|hemorrag|desmaio|falta de ar|dispneia|convuls|torax|peito|infarto|avc|derrame|queda de pa|hematoma|queimadura|choque|intoxica/', $texto);

        $perfil = 'enfermeiro';
        $motivoPerfil = 'A avaliação preliminar indica quadro de baixa complexidade, dentro do escopo da enfermagem.';
        if ($urgente) {
            $perfil = 'medico';
            $motivoPerfil = 'A avaliação preliminar aponta sinais de alarme que exigem avaliação médica.';
        } elseif (preg_match('/pele|rash|vermelhid|mancha|coceira|ferida|curativo|diabetes|açucar|glicose|curativo|medicacao|aplicar|injeç|inje|cateter|sonda|curativo|ostomia|reeducacao|educa[çc]/', $texto)) {
            $motivoPerfil = 'O caso é compatível com acompanhamento de enfermagem e curativos.';
        } elseif (preg_match('/coraç|corac|palpita|press|hipertens|infarto|avc|derrame|diabetes|insulina/glicemia|medicament/i', $texto)) {
            $perfil = 'medico';
            $motivoPerfil = 'Há referência a condição clínica que pede avaliação médica.';
        }

        $doPerfil = array_values(array_filter($candidatos, static function ($c) use ($perfil) {
            return $c['perfil'] === $perfil;
        }));
        if (empty($doPerfil)) {
            $doPerfil = $candidatos;
        }
        $escolhido = reset($doPerfil);

        $classificacao = $urgente ? 'vermelho' : ((int) $escolhido['experiencia'] >= 10 ? 'amarelo' : 'verde');

        return json_encode([
            'classificacao' => $classificacao,
            'justificativa_classificacao' => $urgente
                ? 'Sinais de alarme detectados na descrição do caso; sugere atendimento prioritário.'
                : 'Quadro descrito sem sinais de alarme imediatos; avaliação preliminar de rotina.',
            'perfil_recomendado' => $perfil,
            'justificativa_perfil' => $motivoPerfil,
            'profissional_sugerido_id' => (int) ($escolhido['id'] ?? 0),
            'justificativa_profissional' => sprintf(
                'Primeiro da lista por aderência: %s (%s, %d ano(s) de experiência).',
                $perfil === 'medico' ? 'médico' : 'enfermeiro',
                $escolhido['especialidade'] ?? 'especialidade não informada',
                (int) ($escolhido['experiencia'] ?? 0)
            )
        ]);
    }

    /**
     * Counts AI usages today for a user
     */
    public function contarUsosHoje($usuarioId) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_uso_ia
              WHERE fk_usuario = :fk AND data_consulta = CURDATE()"
        );
        $stmt->execute(['fk' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Records an AI usage
     */
    public function registrarUso($usuarioId) {
        $model = Env::get('GROQ_MODEL', 'openai/gpt-oss-20b');
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_uso_ia (fk_usuario, modelo, quantidade_consultas, data_consulta)
             VALUES (:fk, :modelo, 1, CURDATE())"
        );
        return $stmt->execute(['fk' => $usuarioId, 'modelo' => $model]);
    }
}
