<?php
class EnfermeiroController {
    private $profissionalModel;
    private $pacienteModel;
    private $agendamentoModel;
    
    public function __construct() {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }
        
        if (!RBAC::can(Constants::PERM_ENFERMEIRO_ACESSAR)) {
            header('Location: ' . BASE_URL . '/painel/index');
            exit;
        }
        
        $this->profissionalModel = new Profissional();
        $this->pacienteModel = new Paciente();
        $this->agendamentoModel = new Agendamento();
    }

    public function pendencias() {
        if (!RBAC::can(Constants::PERM_TRIAGEM_GERENCIAR)) {
            Session::flash('error', 'Você não tem permissão para gerenciar triagens.');
            header('Location: ' . BASE_URL . '/enfermeiro/index');
            exit;
        }

        $id_enfermeiro = $this->profissionalModel->getIdByUsuario(Session::get('usuario_id'));
        $triagens = $id_enfermeiro
            ? $this->profissionalModel->getTriagensPendentesEnfermagem($id_enfermeiro)
            : [];

        require_once __DIR__ . '/../views/enfermeiro/pendencias.php';
    }

    public function aprovar_triagem($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Requisição inválida (token de segurança)');
            header('Location: ' . BASE_URL . '/enfermeiro/pendencias');
            exit;
        }

        $id_triagem = $id ? (int) $id : (int) ($_POST['id_triagem'] ?? 0);

        if (!$this->triagemPertenceAoEnfermeiro($id_triagem)) {
            Session::flash('error', 'Triagem não localizada ou sem permissão.');
            header('Location: ' . BASE_URL . '/enfermeiro/pendencias');
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
            'triagem aprovada pela enfermagem',
            [
                'id_triagem' => $id_triagem,
                'id_agendamento' => $id_agendamento,
                'classificacao' => $classificacao
            ]
        );

        Session::flash('success', $id_agendamento
            ? 'Triagem aprovada e visita confirmada.'
            : 'Triagem aprovada.');
        header('Location: ' . BASE_URL . '/enfermeiro/pendencias');
        exit;
    }

    public function recusar_triagem($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Requisição inválida (token de segurança)');
            header('Location: ' . BASE_URL . '/enfermeiro/pendencias');
            exit;
        }

        $id_triagem = $id ? (int) $id : (int) ($_POST['id_triagem'] ?? 0);
        $justificativa = trim($_POST['justificativa'] ?? '');

        if (!$this->triagemPertenceAoEnfermeiro($id_triagem)) {
            Session::flash('error', 'Triagem não localizada ou sem permissão.');
            header('Location: ' . BASE_URL . '/enfermeiro/pendencias');
            exit;
        }

        $this->pacienteModel->recusarTriagem($id_triagem, $justificativa !== '' ? $justificativa : null);

        // Refusing the triage also cancels the visit scheduled from it.
        $id_agendamento = $this->agendamentoModel->cancelarVisitaDaTriagem(
            $id_triagem,
            $justificativa !== '' ? $justificativa : 'Triagem recusada pela enfermagem.'
        );

        Logger::audit(Constants::AUDITORIA_ACAO_RECUSAR_TRIAGEM, [
            'id_triagem' => $id_triagem,
            'id_agendamento' => $id_agendamento,
            'justificativa' => $justificativa
        ]);

        (new Auditoria())->registrar(
            Constants::AUDITORIA_ACAO_RECUSAR_TRIAGEM,
            'triagem recusada pela enfermagem',
            [
                'id_triagem' => $id_triagem,
                'id_agendamento' => $id_agendamento,
                'justificativa' => $justificativa
            ]
        );

        Session::flash('success', 'Triagem recusada.');
        header('Location: ' . BASE_URL . '/enfermeiro/pendencias');
        exit;
    }

    private function triagemPertenceAoEnfermeiro($idTriagem) {
        if (!$idTriagem) {
            return false;
        }
        $id_enfermeiro = $this->profissionalModel->getIdByUsuario(Session::get('usuario_id'));
        return $id_enfermeiro
            && $this->profissionalModel->triagemPertenceAoProfissional($idTriagem, $id_enfermeiro);
    }
    
    public function index() {
        $id_usuario = Session::get('usuario_id');
        $id_profissional = $this->profissionalModel->getIdByUsuario($id_usuario);

        $visitas_hoje = [];
        if ($id_profissional) {
            $visitas_hoje = $this->profissionalModel->contarVisitasHoje($id_profissional);
        }

        $visitas_concluidas_hoje = [];
        if ($id_profissional) {
            $visitas_concluidas_hoje = $this->profissionalModel->contarVisitasConcluidasHoje($id_profissional);
        }

        $fila_atendimentos = [];
        if ($id_profissional) {
            $fila_atendimentos = $this->profissionalModel->getFilaAtendimentosHoje($id_profissional);
        }

        require_once __DIR__ . '/../views/enfermeiro/index.php';
    }

    public function minha_agenda() {
        $id_usuario = Session::get('usuario_id');
        $id_profissional = $this->profissionalModel->getIdByUsuario($id_usuario);

        $agendamentos_futuros = [];
        if ($id_profissional) {
            $agendamentos_futuros = $this->profissionalModel->getAgendamentosFuturos($id_profissional);
        }

        require_once __DIR__ . '/../views/enfermeiro/minha_agenda.php';
    }

    public function historico() {
        $id_usuario = Session::get('usuario_id');
        $id_profissional = $this->profissionalModel->getIdByUsuario($id_usuario);

        $visitas_concluidas = [];
        if ($id_profissional) {
            $visitas_concluidas = $this->profissionalModel->getVisitasConcluidas($id_profissional);
        }

        require_once __DIR__ . '/../views/enfermeiro/historico.php';
    }
}
