<?php
class AgendamentoController {
    private $usuarioModel;
    private $profissionalModel;
    private $pacienteModel;
    private $agendamentoModel;
    private $iaModel;

    public function __construct() {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }
        $this->usuarioModel = new Usuario();
        $this->profissionalModel = new Profissional();
        $this->pacienteModel = new Paciente();
        $this->agendamentoModel = new Agendamento();
        $this->iaModel = new IA();
    }

    public function solicitar_agendamento() {
        $id_paciente = Session::get('usuario_id');

        if (!$id_paciente) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        $rotas_painel = [
            Constants::PERFIL_ADMIN => 'painel',
            Constants::PERFIL_MEDICO => 'medico',
            Constants::PERFIL_ENFERMEIRO => 'enfermeiro',
            Constants::PERFIL_TECNICO_ENFERMAGEM => 'tecnicoenfermagem',
            'paciente'            => 'paciente'
        ];

        $perfil = Session::get('usuario_perfil');

        if (!RBAC::can(Constants::PERM_AGENDAMENTO_SOLICITAR)) {
            $destino = $rotas_painel[$perfil] ?? 'login';
            header('Location: ' . BASE_URL . '/' . $destino . '/index');
            exit;
        }

        $profissionais = $this->profissionalModel->listarAtivos();

        // Admin can schedule for any patient (a selector is rendered instead of
        // the hidden field); any other profile schedules only for their account.
        $ehAdmin = ($perfil === Constants::PERFIL_ADMIN);
        $fk_usuario_hidden = $ehAdmin ? 0 : (int) Session::get('usuario_id');
        $pacientes = $ehAdmin ? $this->pacienteModel->listarTodos() : [];

        require_once __DIR__ . '/../views/paciente/solicitar_agendamento.php';
    }

    public function enviar_dados() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
            exit;
        }

        if (!RBAC::can(Constants::PERM_AGENDAMENTO_SOLICITAR)) {
            Session::flash('error', 'Você não tem permissão para solicitar agendamentos.');
            header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
            exit;
        }

        // Validate CSRF
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Token de segurança inválido');
            header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
            exit;
        }

        // Validate inputs
        $validator = new Validator($_POST);
        $validator->required('fk_usuario')
                  ->required('fk_profissional')
                  ->required('data')
                  ->required('horario_inicio')
                  ->required('horario_fim')
                  ->integer('fk_usuario')
                  ->integer('fk_profissional')
                  ->date('data');

        if ($validator->fails()) {
            Session::flash('error', $validator->getFirstError());
            header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
            exit;
        }

        $data = $validator->getData();
        $fk_profissional = $data['fk_profissional'];
        $data_agendamento = $data['data'];
        $horario_inicio = $data['horario_inicio'];
        $horario_fim = $data['horario_fim'];

        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horario_inicio)
            || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horario_fim)) {
            Session::flash('error', 'Informe o horário de início e de fim (HH:MM).');
            header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
            exit;
        }

            // Business rule: a patient can only schedule for themselves.
            // Only an admin may schedule on behalf of another patient (via the provided ID).
            $perfil = Session::get('usuario_perfil');
            if ($perfil === Constants::PERFIL_ADMIN) {
                $fk_usuario = (int) ($data['fk_usuario'] ?? 0);
            } else {
                $fk_usuario = (int) Session::get('usuario_id');
            }

            if (!$fk_usuario) {
                Session::flash('error', 'Usuário inválido para o agendamento');
                header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
                exit;
            }

            // Logical validation of the time range
            $startParts = explode(':', $horario_inicio);
            $endParts = explode(':', $horario_fim);

            $startMin = (count($startParts) === 2) ? ((int)$startParts[0] * 60 + (int)$startParts[1]) : 0;
            $endMin = (count($endParts) === 2) ? ((int)$endParts[0] * 60 + (int)$endParts[1]) : 0;

            if ($startMin <= 0 || $endMin <= 0 || $endMin <= $startMin) {
                Session::flash('error', 'O horário do fim precisa ser maior que o horário do início');
                header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
                exit;
            }

            // Prevent past dates
            if ($data_agendamento < date('Y-m-d')) {
                Session::flash('error', 'A data do agendamento nao pode estar no passado');
                header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
                exit;
            }

            // Retrieve the patient ID linked to the user ID
            $actualPatientId = $this->pacienteModel->getIdByUsuario($fk_usuario);

            if (!$actualPatientId) {
                Session::flash('error', 'Registro de paciente não localizado para este usuário');
                header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
                exit;
            }

            // Check for scheduling conflict for the professional
            $conflictForProf = $this->agendamentoModel->verificarConflito(
                $fk_profissional, $data_agendamento, $horario_inicio, $horario_fim
            );
            if ($conflictForProf) {
                Session::flash('error', 'Esse profissional ja tem atendimento nesse horario');
                header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
                exit;
            }

            // Check for scheduling conflict for the patient
            $conflictForPatient = $this->agendamentoModel->verificarConflitoPaciente(
                $actualPatientId, $data_agendamento, $horario_inicio, $horario_fim
            );
            if ($conflictForPatient) {
                Session::flash('error', 'Voce ja tem um atendimento marcado nesse horario');
                header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
                exit;
            }

            // Business rule: daily visit limit and pause window of the professional.
            $limitesProf = $this->agendamentoModel->getLimitesProfissional($fk_profissional);
            $limiteDiario = (int) ($limitesProf['limite_diario'] ?? 0);
            if ($limiteDiario > 0) {
                $contagemDia = $this->agendamentoModel->contarAgendamentosDia($fk_profissional, $data_agendamento);
                if ($contagemDia >= $limiteDiario) {
                    Session::flash('error', "Este profissional ja atingiu o limite de {$limiteDiario} atendimentos nesse dia");
                    header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
                    exit;
                }
            }
            if ($limitesProf && $this->agendamentoModel->conflitaComPausa($limitesProf, $horario_inicio, $horario_fim)) {
                Session::flash('error', 'Este horario coincide com a pausa do profissional');
                header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
                exit;
            }

            try {
                // Create the appointment
                $resultado = $this->agendamentoModel->criar([
                    'fk_paciente' => $actualPatientId,
                    'fk_profissional' => $fk_profissional,
                    'data_agendamento' => $data_agendamento,
                    'horario_inicio' => $horario_inicio,
                    'horario_fim' => $horario_fim,
                    'status' => Constants::AGENDAMENTO_PENDENTE
                ]);

                if ($resultado) {
                    $id_agendamento = $this->agendamentoModel->getUltimoIdInserido();

                    // Automatic AI triage/encaminhamento analysis (pulseira + professional recommendation).
                    // Runs every request so the doctor already sees the suggestion in pendencias.
                    $this->gerarAnaliseIaAgendamento($id_agendamento, $actualPatientId);

                    Session::flash('success', 'Agendamento solicitado com sucesso! Aguarde a confirmação do profissional.');
                    
                    Logger::audit(Constants::AUDITORIA_ACAO_CRIAR_AGENDAMENTO, [
                        'fk_paciente' => $actualPatientId,
                        'fk_profissional' => $fk_profissional,
                        'data' => $data_agendamento,
                        'horario_inicio' => $horario_inicio
                    ]);

                    (new Auditoria())->registrar(
                        Constants::AUDITORIA_ACAO_CRIAR_AGENDAMENTO,
                        'novo agendamento solicitado',
                        ['fk_paciente' => $actualPatientId, 'data' => $data_agendamento]
                    );

                    // Notificacao WhatsApp (Evolution API): best-effort,
                    // uma falha de envio nunca desfaz o agendamento.
                    (new WhatsAppService())->enviarConfirmacaoAgendamento(
                        (int) $fk_usuario,
                        (string) ($this->pacienteModel->getById($actualPatientId)['nome'] ?? ''),
                        $data_agendamento,
                        $horario_inicio,
                        $horario_fim
                    );

                    header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
                    exit;
                } else {
                    Session::flash('error', 'Erro ao criar agendamento');
                    header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
                    exit;
                }
            } catch (DatabaseException $e) {
                Logger::error("Erro ao criar agendamento", ['error' => $e->getMessage()]);
                Session::flash('error', 'Erro ao criar agendamento');
                header('Location: ' . BASE_URL . '/agendamento/solicitar_agendamento');
                exit;
            }
    }

    /**
     * Generates and stores the AI triage/encaminhamento analysis for a scheduling.
     * Recommendation only: the doctor can override it in pendencias.
     */
    private function gerarAnaliseIaAgendamento($idAgendamento, $idPaciente) {
        $paciente = $this->pacienteModel->getById($idPaciente) ?: [];
        $anamnese = $this->pacienteModel->getAnamnese($idPaciente);
        $paciente = array_merge($anamnese, $paciente);
        $triagem = $this->pacienteModel->getUltimaTriagem($idPaciente);

        $analise = $this->iaModel->analisarEncaminhamento([
            'solicitante' => $triagem['nome_solicitante'] ?? '',
            'classificacao_solicitante' => $triagem['classificacao'] ?? '',
            'idade_sexo' => $this->montarIdadeSexo($paciente ?: []),
            'alergias' => $paciente['alergias'] ?? '',
            'doencas_previas' => $paciente['doencas_previas'] ?? '',
            'medicamentos_uso' => $paciente['medicamentos_uso'] ?? '',
            'cirurgias' => $paciente['cirurgias'] ?? ''
        ]);

        $this->agendamentoModel->salvarAnaliseIA($idAgendamento, $analise);

        Logger::audit(Constants::AUDITORIA_ACAO_ANALISE_IA_ENCAMINHAMENTO, [
            'id_agendamento' => $idAgendamento,
            'classificacao' => $analise['classificacao'],
            'profissional_indicado' => $analise['profissional_indicado']
        ]);

        (new Auditoria())->registrar(
            Constants::AUDITORIA_ACAO_ANALISE_IA_ENCAMINHAMENTO,
            'análise IA de encaminhamento gerada automaticamente',
            [
                'id_agendamento' => $idAgendamento,
                'classificacao' => $analise['classificacao'],
                'profissional_indicado' => $analise['profissional_indicado']
            ]
        );
    }

    /**
     * Builds a short "idade/sexo" descriptor from the patient record
     */
    private function montarIdadeSexo($paciente) {
        if (empty($paciente['data_nascimento'])) {
            return '';
        }
        $anos = (int) floor((time() - strtotime($paciente['data_nascimento'])) / 31536000);
        $sexo = trim($paciente['sexo'] ?? '');
        return trim($sexo . ', ' . max(0, $anos) . ' anos', ', ');
    }
}
