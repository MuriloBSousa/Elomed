<?php
class MedicoController {
    private $usuarioModel;
    private $protocoloModel;
    private $profissionalModel;
    private $agendamentoModel;
    private $prontuarioModel;
    private $iaController;
    private $pacienteModel;
    
    public function __construct() {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        if (!RBAC::can(Constants::PERM_MEDICO_ACESSAR)) {
            header('Location: ' . BASE_URL . '/painel/index');
            exit;
        }

        $this->usuarioModel = new Usuario();
        $this->protocoloModel = new Protocolo();
        $this->profissionalModel = new Profissional();
        $this->agendamentoModel = new Agendamento();
        $this->prontuarioModel = new Prontuario();
        $this->iaController = new IAController();
        $this->pacienteModel = new Paciente();
    }

    public function index() {
        $id_usuario = Session::get('usuario_id');
        $id_profissional = $this->profissionalModel->getIdByUsuario($id_usuario);

        $nome_profissional = [];
        if ($id_profissional) {
            $nome_profissional = $this->profissionalModel->getDadosComNome($id_profissional);
        }

        $visitas_hoje = [];
        if ($id_profissional) {
            $visitas_hoje = $this->profissionalModel->contarVisitasHoje($id_profissional);
        }

        $visitas_concluidas_hoje = [];
        if ($id_profissional) {
            $visitas_concluidas_hoje = $this->profissionalModel->contarVisitasConcluidasHoje($id_profissional);
        }

        $consulta_hoje = [];
        if ($id_profissional) {
            $consulta_hoje = $this->profissionalModel->getFilaAtendimentosHoje($id_profissional);
        }

        require_once __DIR__ . '/../views/medico/index.php';
    }

    public function pendencias() {
        $id_usuario = Session::get('usuario_id');
        $id_profissional = $this->profissionalModel->getIdByUsuario($id_usuario);

        $aprovar_triagem = null; 
        if ($id_profissional) {
            $aprovar_triagem = $this->profissionalModel->getTriagensPendentes($id_profissional);
        }

        $aprovar_agendamento = [];
        if ($id_profissional) {
            $aprovar_agendamento = $this->profissionalModel->getAgendamentosPendentes($id_profissional);
        }

        $agendamentos_futuros = [];
        if ($id_profissional) {
            $agendamentos_futuros = $this->profissionalModel->getAgendamentosFuturos($id_profissional);
        }

        $equipe_encaminhamento = $this->profissionalModel->listarPorPerfis([
            Constants::PERFIL_ENFERMEIRO,
            Constants::PERFIL_TECNICO_ENFERMAGEM
        ]);

        $pode_aprovar_triagem = RBAC::can(Constants::PERM_TRIAGEM_APROVAR);

        require_once __DIR__ . '/../views/medico/pendencias.php';
    }
    
    public function minha_agenda() {
        $id_usuario = Session::get('usuario_id');
        $id_profissional = $this->profissionalModel->getIdByUsuario($id_usuario);

        $agendamentos_futuros = [];
        if ($id_profissional) {
            $agendamentos_futuros = $this->profissionalModel->getAgendamentosFuturos($id_profissional);
        }

        $agendamentos_passados = [];
        if ($id_profissional) {
            $agendamentos_passados = $this->profissionalModel->getAgendamentosPassados($id_profissional);
        }

        require_once __DIR__ . '/../views/medico/minha_agenda.php';
    }
    
    public function historico_visitas() {
        $id_usuario = Session::get('usuario_id');
        $id_profissional = $this->profissionalModel->getIdByUsuario($id_usuario);

        $visitas_concluidas = [];
        if ($id_profissional) {
            $visitas_concluidas = $this->profissionalModel->getVisitasConcluidas($id_profissional);
        }

        require_once __DIR__ . '/../views/medico/historico_visitas.php';
    }

    public function aceitar($id_pos = null) {
        // Business rule: appointment actions require POST + CSRF
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Requisição inválida (token de segurança)');
            header('Location: ' . BASE_URL . '/medico/pendencias');
            exit;
        }

        $id_agendamento = $id_pos ? (int) $id_pos : (int) ($_POST['id'] ?? 0);

        if (!$id_agendamento || !$this->podeAgirNoAgendamento($id_agendamento)) {
            Session::flash('error', 'Não foi possível confirmar: agendamento ou permissão inválida.');
            header('Location: ' . BASE_URL . '/medico/pendencias');
            exit;
        }

        $ag = $this->agendamentoModel->getById($id_agendamento);
        if (!$ag || $ag['status'] !== Constants::AGENDAMENTO_PENDENTE) {
            Session::flash('error', 'Agendamento não está pendente.');
            header('Location: ' . BASE_URL . '/medico/pendencias');
            exit;
        }

        // Doctor's manual override for risk classification (pulseira)
        $classificacao = trim($_POST['classificacao'] ?? '');
        if (!in_array($classificacao, ['vermelho', 'amarelo', 'verde'], true)) {
            $classificacao = $ag['classificacao_ia'] ?? null;
        }
        if ($classificacao) {
            $this->agendamentoModel->salvarClassificacaoFinal($id_agendamento, $classificacao);
        }

        // Doctor assigns (or reassigns) the professional to carry out the visit
        $fk_novo_profissional = (int) ($_POST['fk_profissional_novo'] ?? 0);
        $justificativa_encaminhamento = trim($_POST['justificativa_encaminhamento'] ?? '');
        if ($fk_novo_profissional > 0 && $fk_novo_profissional !== (int) $ag['fk_profissional']) {
            $this->agendamentoModel->atribuirProfissional(
                $id_agendamento,
                $fk_novo_profissional,
                $justificativa_encaminhamento !== '' ? $justificativa_encaminhamento : null
            );

            Logger::audit(Constants::AUDITORIA_ACAO_ATRIBUIR_ENCAMINHAMENTO, [
                'id_agendamento' => $id_agendamento,
                'fk_profissional_anterior' => (int) $ag['fk_profissional'],
                'fk_profissional_novo' => $fk_novo_profissional,
                'justificativa' => $justificativa_encaminhamento
            ]);

            (new Auditoria())->registrar(
                Constants::AUDITORIA_ACAO_ATRIBUIR_ENCAMINHAMENTO,
                'consulta atribuída a outro profissional',
                [
                    'id_agendamento' => $id_agendamento,
                    'fk_profissional_novo' => $fk_novo_profissional,
                    'justificativa' => $justificativa_encaminhamento
                ]
            );
        }

        $this->agendamentoModel->atualizarStatusComCondicao(
            $id_agendamento,
            Constants::AGENDAMENTO_AGENDADO,
            Constants::AGENDAMENTO_PENDENTE
        );
        $this->agendamentoModel->gerarLembrete($id_agendamento);

        // Approve the patient's pending triage (if any) with the final classification
        $this->aprovarTriagemDoAgendamento($id_agendamento, $classificacao);

        Logger::audit(Constants::AUDITORIA_ACAO_ATUALIZAR_AGENDAMENTO, [
            'id_agendamento' => $id_agendamento,
            'novo_status' => Constants::AGENDAMENTO_AGENDADO,
            'classificacao_final' => $classificacao
        ]);

        (new Auditoria())->registrar(
            Constants::AUDITORIA_ACAO_ATUALIZAR_AGENDAMENTO,
            'agendamento confirmado',
            ['id_agendamento' => $id_agendamento, 'novo_status' => Constants::AGENDAMENTO_AGENDADO]
        );

        Session::flash('success', 'Agendamento confirmado e lembrete gerado.');
        header('Location: ' . BASE_URL . '/medico/pendencias');
        exit;
    }

    public function rejeitar($id_pos = null) {
        // Business rule: appointment actions require POST + CSRF
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Requisição inválida (token de segurança)');
            header('Location: ' . BASE_URL . '/medico/pendencias');
            exit;
        }

        $id_agendamento = $id_pos ? (int) $id_pos : (int) ($_POST['id'] ?? 0);
        $justificativa  = trim($_POST['justificativa'] ?? '');

        if ($id_agendamento && $this->podeAgirNoAgendamento($id_agendamento)) {
            if ($justificativa === '') {
                $justificativa = 'Nao informado pelo profissional';
            }
            $this->agendamentoModel->cancelarComJustificativa($id_agendamento, $justificativa);

            Logger::audit(Constants::AUDITORIA_ACAO_ATUALIZAR_AGENDAMENTO, [
                'id_agendamento' => $id_agendamento,
                'novo_status' => Constants::AGENDAMENTO_CANCELADO,
                'justificativa' => $justificativa
            ]);

            (new Auditoria())->registrar(
                Constants::AUDITORIA_ACAO_ATUALIZAR_AGENDAMENTO,
                'agendamento cancelado',
                ['id_agendamento' => $id_agendamento, 'justificativa' => $justificativa]
            );
            Session::flash('success', 'Agendamento cancelado.');
        } else {
            Session::flash('error', 'Não foi possível cancelar: agenda ou permissão inválida.');
        }

        header('Location: ' . BASE_URL . '/medico/pendencias');
        exit;
    }

    /**
     * Approves a pending triage from the triagens pendentes list
     */
    public function aprovar_triagem($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Requisição inválida (token de segurança)');
            header('Location: ' . BASE_URL . '/medico/pendencias');
            exit;
        }

        $id_triagem = $id ? (int) $id : (int) ($_POST['id_triagem'] ?? 0);
        if (!$id_triagem || !$this->podeAgirNaTriagem($id_triagem)) {
            Session::flash('error', 'Triagem não localizada ou sem permissão.');
            header('Location: ' . BASE_URL . '/medico/pendencias');
            exit;
        }

        $classificacao = trim($_POST['classificacao'] ?? '');
        if (!in_array($classificacao, ['vermelho', 'amarelo', 'verde'], true)) {
            $classificacao = null;
        }

        $this->pacienteModel->aprovarTriagem($id_triagem, $classificacao);

        // Approving the triage also confirms the visit the técnico de
        // enfermagem scheduled from it (pendente -> agendado).
        $id_agendamento = $this->agendamentoModel->confirmarVisitaDaTriagem($id_triagem);

        Logger::audit(Constants::AUDITORIA_ACAO_APROVAR_TRIAGEM, [
            'id_triagem' => $id_triagem,
            'id_agendamento' => $id_agendamento,
            'classificacao' => $classificacao
        ]);

        (new Auditoria())->registrar(
            Constants::AUDITORIA_ACAO_APROVAR_TRIAGEM,
            'triagem aprovada',
            [
                'id_triagem' => $id_triagem,
                'id_agendamento' => $id_agendamento,
                'classificacao' => $classificacao
            ]
        );

        Session::flash('success', $id_agendamento
            ? 'Triagem aprovada e visita confirmada.'
            : 'Triagem aprovada.');
        header('Location: ' . BASE_URL . '/medico/pendencias');
        exit;
    }

    /**
     * Refuses a pending triage from the triagens pendentes list
     */
    public function rejeitar_triagem($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Requisição inválida (token de segurança)');
            header('Location: ' . BASE_URL . '/medico/pendencias');
            exit;
        }

        $id_triagem = $id ? (int) $id : (int) ($_POST['id_triagem'] ?? 0);
        $justificativa = trim($_POST['justificativa'] ?? '');
        if (!$id_triagem || !$this->podeAgirNaTriagem($id_triagem)) {
            Session::flash('error', 'Triagem não localizada ou sem permissão.');
            header('Location: ' . BASE_URL . '/medico/pendencias');
            exit;
        }

        $this->pacienteModel->recusarTriagem($id_triagem, $justificativa !== '' ? $justificativa : null);

        // Refusing the triage also cancels the visit scheduled from it.
        $id_agendamento = $this->agendamentoModel->cancelarVisitaDaTriagem(
            $id_triagem,
            $justificativa !== '' ? $justificativa : 'Triagem recusada pelo médico.'
        );

        Logger::audit(Constants::AUDITORIA_ACAO_RECUSAR_TRIAGEM, [
            'id_triagem' => $id_triagem,
            'id_agendamento' => $id_agendamento,
            'justificativa' => $justificativa
        ]);

        (new Auditoria())->registrar(
            Constants::AUDITORIA_ACAO_RECUSAR_TRIAGEM,
            'triagem recusada',
            [
                'id_triagem' => $id_triagem,
                'id_agendamento' => $id_agendamento,
                'justificativa' => $justificativa
            ]
        );

        Session::flash('success', 'Triagem recusada.');
        header('Location: ' . BASE_URL . '/medico/pendencias');
        exit;
    }

    /**
     * Approves the patient's pending triage when the scheduling is accepted.
     */
    private function aprovarTriagemDoAgendamento($idAgendamento, $classificacao = null) {
        $triagem = $this->agendamentoModel->getTriagemPendente($idAgendamento);
        if (!$triagem) {
            return;
        }
        $this->pacienteModel->aprovarTriagem($triagem['id'], $classificacao);
    }

    /**
     * Business rule: only the professional who owns the schedule may act on the appointment.
     * Administrators may act on any schedule.
     */
    private function podeAgirNoAgendamento($id_agendamento): bool {
        $perfil = Session::get('usuario_perfil');
        if ($perfil === Constants::PERFIL_ADMIN) {
            return true;
        }
        $id_profissional = $this->profissionalModel->getIdByUsuario(Session::get('usuario_id'));
        return $id_profissional && $this->agendamentoModel->belongsToProfissional($id_agendamento, $id_profissional);
    }

    /**
     * Business rule: only the professional whose pending schedule references that patient may act on the triage.
     * Administrators may act on any triage.
     */
    private function podeAgirNaTriagem($idTriagem): bool {
        $perfil = Session::get('usuario_perfil');
        if ($perfil === Constants::PERFIL_ADMIN) {
            return true;
        }
        $id_profissional = $this->profissionalModel->getIdByUsuario(Session::get('usuario_id'));
        return $id_profissional && $this->profissionalModel->triagemPertenceAoProfissional($idTriagem, $id_profissional);
    }

    public function iniciar_atendimento($id = null) {
        $id_agendamento = $id ? (int) $id : (isset($_GET['id']) ? (int) $_GET['id'] : null);

        if (!$id_agendamento || !$this->podeAgirNoAgendamento($id_agendamento)) {
            Session::flash('error', 'Agendamento não localizado ou sem permissão para atendê-lo.');
            header('Location: ' . BASE_URL . '/medico/minha_agenda');
            exit;
        }

        $agendamento = $this->agendamentoModel->getById($id_agendamento);
        if (!$agendamento) {
            Session::flash('error', 'Agendamento não encontrado.');
            header('Location: ' . BASE_URL . '/medico/minha_agenda');
            exit;
        }

        // Starting the attendance screen turns the visit into "em_andamento"
        // (only when it was still just scheduled) so the admin panel reflects it.
        if ($agendamento['status'] === Constants::AGENDAMENTO_AGENDADO) {
            $this->agendamentoModel->atualizarStatusComCondicao(
                $id_agendamento,
                Constants::AGENDAMENTO_EM_ANDAMENTO,
                Constants::AGENDAMENTO_AGENDADO
            );
            $agendamento['status'] = Constants::AGENDAMENTO_EM_ANDAMENTO;
        }

        $protocolos = $this->protocoloModel->getAllAtivos();
        
        require_once __DIR__ . '/../views/medico/iniciar_atendimento.php';
    }

    /**
     * Marks an appointment as "ausente" (the patient did not show up).
     */
    public function marcar_ausente($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Requisição inválida (token de segurança)');
            header('Location: ' . BASE_URL . '/medico/minha_agenda');
            exit;
        }

        $id_agendamento = $id ? (int) $id : (int) ($_POST['id'] ?? 0);
        if (!$id_agendamento || !$this->podeAgirNoAgendamento($id_agendamento)) {
            Session::flash('error', 'Agendamento não localizado ou sem permissão.');
            header('Location: ' . BASE_URL . '/medico/minha_agenda');
            exit;
        }

        $ok = $this->agendamentoModel->atualizarStatusComCondicao(
            $id_agendamento,
            Constants::AGENDAMENTO_AUSENTE,
            Constants::AGENDAMENTO_AGENDADO
        );

        if (!$ok) {
            Session::flash('error', 'Só é possível marcar ausência em consultas confirmadas.');
            header('Location: ' . BASE_URL . '/medico/minha_agenda');
            exit;
        }

        Logger::audit(Constants::AUDITORIA_ACAO_ATUALIZAR_AGENDAMENTO, [
            'id_agendamento' => $id_agendamento,
            'novo_status' => Constants::AGENDAMENTO_AUSENTE
        ]);

        (new Auditoria())->registrar(
            Constants::AUDITORIA_ACAO_ATUALIZAR_AGENDAMENTO,
            'paciente ausente marcado',
            ['id_agendamento' => $id_agendamento]
        );

        Session::flash('success', 'Paciente marcado como ausente.');
        header('Location: ' . BASE_URL . '/medico/minha_agenda');
        exit;
    }

    /**
     * Returns protocol questions via AJAX
     */
    public function get_perguntas_protocolo($id = null) {
        header('Content-Type: application/json');
        
        $protocoloId = $id ? (int) $id : (isset($_GET['protocolo_id']) ? (int) $_GET['protocolo_id'] : null);
        
        if (!$protocoloId) {
            echo json_encode(['success' => false, 'error' => 'Protocolo ID não fornecido']);
            exit;
        }
        
        $perguntas = $this->protocoloModel->getPerguntas($protocoloId);
        
        echo json_encode([
            'success' => true,
            'perguntas' => $perguntas
        ]);
        exit;
    }

    /**
     * Processes the anamnesis using the AI controller
     */
    public function dados_atendimento() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/medico/index');
            exit;
        }

        // Validate CSRF
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Token de segurança inválido');
            header('Location: ' . BASE_URL . '/medico/index');
            exit;
        }

        // Business rule: the schedule must belong to the logged professional
        // (or an administrator). Without it, a forged id_agendamento would let
        // any professional read/overwrite another professional's consultation.
        $idAgendamento = (int) ($_POST['id_agendamento'] ?? 0);
        if ($idAgendamento > 0 && !$this->podeAgirNoAgendamento($idAgendamento)) {
            Session::flash('error', 'Este agendamento pertence a outro profissional.');
            header('Location: ' . BASE_URL . '/medico/index');
            exit;
        }

        // Use the IAController to generate the diagnosis
        $dados_view = $this->iaController->gerar_diagnostico();

        // Save consultation data to the medical record
        $id_agendamento = $dados_view['id_agendamento'];
        $queixa = $dados_view['queixa'];
        $historico = $dados_view['historico'];
        $insights = $dados_view['insights'];

        if ($id_agendamento) {
            $this->salvarProntuario($id_agendamento, $queixa, $historico, $insights);
        }

        // Load the diagnosis view with the dynamic data
        require_once __DIR__ . '/../views/medico/diagnostico_ia.php';
    }

    /**
     * Saves consultation data to the patient's medical record
     */
    private function salvarProntuario($id_agendamento, $queixa, $historico, $insights) {
        $id_paciente = $this->agendamentoModel->getPacienteId($id_agendamento);
        
        if ($id_paciente) {
            $prontuario = $this->prontuarioModel->getByAgendamento($id_agendamento);
            $insights_json = json_encode($insights);
            
            if ($prontuario) {
                $this->prontuarioModel->atualizar($prontuario['id'], [
                    'queixa' => $queixa,
                    'historico' => $historico,
                    'analise_ia' => $insights_json
                ]);
                
                Logger::audit(Constants::AUDITORIA_ACAO_ATUALIZAR_PRONTUARIO, [
                    'id_prontuario' => $prontuario['id'],
                    'id_agendamento' => $id_agendamento
                ]);

                (new Auditoria())->registrar(
                    Constants::AUDITORIA_ACAO_ATUALIZAR_PRONTUARIO,
                    'prontuario atualizado',
                    ['id_prontuario' => $prontuario['id'], 'id_agendamento' => $id_agendamento]
                );
            } else {
                $this->prontuarioModel->criar([
                    'fk_paciente' => $id_paciente,
                    'fk_agendamento' => $id_agendamento,
                    'queixa' => $queixa,
                    'historico' => $historico,
                    'analise_ia' => $insights_json
                ]);
                
                Logger::audit(Constants::AUDITORIA_ACAO_CRIAR_PRONTUARIO, [
                    'fk_paciente' => $id_paciente,
                    'fk_agendamento' => $id_agendamento
                ]);

                (new Auditoria())->registrar(
                    Constants::AUDITORIA_ACAO_CRIAR_PRONTUARIO,
                    'prontuario criado',
                    ['fk_paciente' => $id_paciente, 'fk_agendamento' => $id_agendamento]
                );
            }
        }
    }

    /**
     * Saves the final diagnosis and medical conduct to the medical record
     */
    public function salvar_diagnostico_final() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/medico/index');
            exit;
        }

        // Validate CSRF
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Token de segurança inválido');
            header('Location: ' . BASE_URL . '/medico/index');
            exit;
        }

        $id_agendamento = $_POST['id_agendamento'] ?? null;
        $diagnostico_final = trim($_POST['diagnostico_final'] ?? '');
        $conduta_medica = trim($_POST['ajuste_conduta'] ?? '');

        // Business rule: the diagnosis is the clinical record of the visit, so
        // it cannot be empty and the status may only move em_andamento -> concluido.
        if (!$id_agendamento || $diagnostico_final === '') {
            Session::flash('error', 'Informe o diagnóstico final antes de concluir o atendimento.');
            header('Location: ' . BASE_URL . '/medico/minha_agenda');
            exit;
        }

        if ($this->podeAgirNoAgendamento((int) $id_agendamento)) {
            $this->prontuarioModel->atualizarDiagnosticoFinal($id_agendamento, $diagnostico_final, $conduta_medica);
            $this->agendamentoModel->atualizarStatusComCondicao(
                (int) $id_agendamento,
                Constants::AGENDAMENTO_CONCLUIDO,
                Constants::AGENDAMENTO_EM_ANDAMENTO
            );
            
            Logger::audit(Constants::AUDITORIA_ACAO_ATUALIZAR_PRONTUARIO, [
                'id_agendamento' => $id_agendamento,
                'diagnostico_final' => substr($diagnostico_final, 0, 100)
            ]);

            (new Auditoria())->registrar(
                Constants::AUDITORIA_ACAO_ATUALIZAR_PRONTUARIO,
                'diagnostico final salvo',
                ['id_agendamento' => $id_agendamento]
            );
            Session::flash('success', 'Diagnóstico final e conduta salvos no prontuário.');
        } else {
            Session::flash('error', 'Não foi possível salvar: atendimento não localizado ou sem permissão.');
        }

        header('Location: ' . BASE_URL . '/medico/minha_agenda');
        exit;
    }
}
