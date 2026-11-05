<?php
class TecnicoEnfermagemController {
    private $profissionalModel;
    private $pacienteModel;
    private $agendamentoModel;
    private $triagemModel;
    
    public function __construct() {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }
        
        if (!RBAC::can(Constants::PERM_TECNICO_ACESSAR)) {
            header('Location: ' . BASE_URL . '/painel/index');
            exit;
        }
        
        $this->profissionalModel = new Profissional();
        $this->pacienteModel = new Paciente();
        $this->agendamentoModel = new Agendamento();
        $this->triagemModel = new Triagem();
    }

    /**
     * Triage analysis queue: the técnico de enfermagem analyzes the triage
     * requested by the patient and forwards it to the appropriate professional.
     */
    public function triagens() {
        if (!RBAC::can(Constants::PERM_TRIAGEM_ANALISAR)) {
            Session::flash('error', 'Você não tem permissão para analisar triagens.');
            header('Location: ' . BASE_URL . '/tecnicoenfermagem/index');
            exit;
        }

        $triagens = $this->profissionalModel->getTriagensParaAnalise();

        $base = $this->profissionalModel->listarPorPerfis([
            Constants::PERFIL_MEDICO,
            Constants::PERFIL_ENFERMEIRO
        ]);

        $porTriagem = [];
        foreach ($triagens as $tri) {
            $id = (int) $tri['id'];

            $ranking = $this->profissionalModel->rankearParaTriagem($base, [
                $tri['queixa_principal'] ?? '',
                $tri['descricao_sintomas'] ?? ''
            ]);

            $porTriagem[$id] = [
                'profissionais' => $ranking,
                'sugerido' => $this->profissionalModel->getSugeridoPelaIA($tri['ia_profissional_sugerido'] ?? null)
            ];
        }

        require_once __DIR__ . '/../views/tecnico_enfermagem/triagens.php';
    }

    /**
     * Analyzes a pending triage and forwards it to the appropriate professional
     * (médico or enfermeiro), storing the final classification (pulseira) and
     * the optional justification for the encaminhamento.
     *
     * The encaminhamento also becomes a scheduling: the técnico de enfermagem
     * is the one who defines the date and the time of the visit (the patient
     * keeps choosing the professional and the time only in the regular
     * "Solicitar Agendamento" flow). The visit is created in the patient's
     * name with status 'pendente', waiting for the target professional to
     * confirm it, and stays linked to the triage through fk_triagem.
     */
    public function encaminhar($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Requisição inválida (token de segurança)');
            header('Location: ' . BASE_URL . '/tecnicoenfermagem/triagens');
            exit;
        }

        if (!RBAC::can(Constants::PERM_TRIAGEM_ANALISAR)) {
            Session::flash('error', 'Você não tem permissão para analisar triagens.');
            header('Location: ' . BASE_URL . '/tecnicoenfermagem/index');
            exit;
        }

        $id_triagem = $id ? (int) $id : (int) ($_POST['id_triagem'] ?? 0);
        $fk_profissional = (int) ($_POST['fk_profissional'] ?? 0);
        $classificacao = trim($_POST['classificacao'] ?? '');
        $justificativa = trim($_POST['justificativa_encaminhamento'] ?? '');
        $data = trim($_POST['data'] ?? '');
        $horario_inicio = trim($_POST['horario_inicio'] ?? '');
        $horario_fim = trim($_POST['horario_fim'] ?? '');

        $voltar = BASE_URL . '/tecnicoenfermagem/triagens';

        if (!$id_triagem || !$this->profissionalModel->triagemPodeSerAnalisada($id_triagem)) {
            Session::flash('error', 'Triagem não localizada ou já encaminhada.');
            header('Location: ' . BASE_URL . '/tecnicoenfermagem/triagens');
            exit;
        }

        if (!in_array($classificacao, ['vermelho', 'amarelo', 'verde'], true)) {
            Session::flash('error', 'Informe a classificação de risco (pulseira) da triagem.');
            header('Location: ' . BASE_URL . '/tecnicoenfermagem/triagens');
            exit;
        }

        if ($fk_profissional <= 0) {
            Session::flash('error', 'Selecione o profissional (médico ou enfermeiro) para o encaminhamento.');
            header('Location: ' . BASE_URL . '/tecnicoenfermagem/triagens');
            exit;
        }

        // The target must be an active médico or enfermeiro.
        $perfisAlvo = $this->profissionalModel->listarPorPerfis([
            Constants::PERFIL_MEDICO,
            Constants::PERFIL_ENFERMEIRO
        ]);
        if (!in_array($fk_profissional, array_column($perfisAlvo, 'id_profissional'), true)) {
            Session::flash('error', 'O profissional de destino deve ser um médico ou enfermeiro ativo.');
            header('Location: ' . BASE_URL . '/tecnicoenfermagem/triagens');
            exit;
        }

        $id_tecnico = $this->profissionalModel->getIdByUsuario(Session::get('usuario_id'));

        // The visit is booked for the patient who requested the triage.
        $triagem = $this->triagemModel->find($id_triagem);
        $id_paciente = (int) ($triagem['fk_paciente'] ?? 0);
        if (!$id_paciente) {
            Session::flash('error', 'Paciente da triagem não localizado.');
            header('Location: ' . $voltar);
            exit;
        }

        // Date/time chosen by the técnico de enfermagem.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            Session::flash('error', 'Informe a data da visita.');
            header('Location: ' . $voltar);
            exit;
        }
        if ($data < date('Y-m-d')) {
            Session::flash('error', 'A data da visita não pode estar no passado.');
            header('Location: ' . $voltar);
            exit;
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horario_inicio)
            || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horario_fim)) {
            Session::flash('error', 'Informe o horário de início e de fim da visita.');
            header('Location: ' . $voltar);
            exit;
        }

        list($iniMin, $fimMin) = $this->minutosDoDia($horario_inicio, $horario_fim);
        if ($fimMin <= $iniMin) {
            Session::flash('error', 'O horário do fim precisa ser maior que o horário do início.');
            header('Location: ' . $voltar);
            exit;
        }

        if ($this->agendamentoModel->verificarConflito($fk_profissional, $data, $horario_inicio, $horario_fim)) {
            Session::flash('error', 'Este profissional já possui uma visita nesse horário.');
            header('Location: ' . $voltar);
            exit;
        }
        if ($this->agendamentoModel->verificarConflitoPaciente($id_paciente, $data, $horario_inicio, $horario_fim)) {
            Session::flash('error', 'O paciente já possui uma visita marcada nesse horário.');
            header('Location: ' . $voltar);
            exit;
        }

        $limites = $this->agendamentoModel->getLimitesProfissional($fk_profissional);
        $limite_diario = (int) ($limites['limite_diario'] ?? 0);
        if ($limite_diario > 0
            && $this->agendamentoModel->contarAgendamentosDia($fk_profissional, $data) >= $limite_diario) {
            Session::flash('error', "Este profissional já atingiu o limite de {$limite_diario} visitas nesse dia.");
            header('Location: ' . $voltar);
            exit;
        }
        if ($limites && $this->agendamentoModel->conflitaComPausa($limites, $horario_inicio, $horario_fim)) {
            Session::flash('error', 'Este horário coincide com a pausa do profissional.');
            header('Location: ' . $voltar);
            exit;
        }

        $db = Database::getConnection();
        $id_agendamento = null;
        try {
            $db->beginTransaction();

            // The encaminhamento itself becomes the scheduling of the visit.
            $criou = $this->agendamentoModel->criar([
                'fk_paciente' => $id_paciente,
                'fk_profissional' => $fk_profissional,
                'data_agendamento' => $data,
                'horario_inicio' => $horario_inicio,
                'horario_fim' => $horario_fim,
                'status' => Constants::AGENDAMENTO_PENDENTE,
                'duracao_minutos' => $fimMin - $iniMin,
                'fk_triagem' => $id_triagem
            ]);

            $encaminhou = $criou && $this->profissionalModel->encaminharTriagem(
                $id_triagem,
                $fk_profissional,
                $id_tecnico,
                $classificacao,
                $justificativa !== '' ? $justificativa : null
            );

            if (!$encaminhou) {
                $db->rollBack();
                Session::flash('error', 'Não foi possível encaminhar a triagem.');
                header('Location: ' . $voltar);
                exit;
            }

            $id_agendamento = (int) $this->agendamentoModel->getUltimoIdInserido();
            $db->commit();
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Logger::error('Erro ao agendar visita originada de triagem', ['error' => $e->getMessage()]);
            Session::flash('error', 'Não foi possível encaminhar a triagem.');
            header('Location: ' . $voltar);
            exit;
        }

        Logger::audit(Constants::AUDITORIA_ACAO_ENCAMINHAR_TRIAGEM, [
            'id_triagem' => $id_triagem,
            'id_agendamento' => $id_agendamento,
            'fk_profissional' => $fk_profissional,
            'classificacao' => $classificacao,
            'data' => $data,
            'horario_inicio' => $horario_inicio
        ]);

        (new Auditoria())->registrar(
            Constants::AUDITORIA_ACAO_ENCAMINHAR_TRIAGEM,
            'triagem analisada e encaminhada pelo técnico de enfermagem',
            [
                'id_triagem' => $id_triagem,
                'id_agendamento' => $id_agendamento,
                'fk_profissional' => $fk_profissional,
                'classificacao' => $classificacao,
                'data' => $data,
                'horario_inicio' => $horario_inicio
            ]
        );

        Session::flash('success', 'Triagem encaminhada e visita agendada para ' . date('d/m/Y', strtotime($data)) . ' às ' . substr($horario_inicio, 0, 5) . '. Aguarde a confirmação do profissional.');
        header('Location: ' . $voltar);
        exit;
    }

    /**
     * Converts two HH:MM strings into the number of minutes since midnight.
     */
    private function minutosDoDia($horario_inicio, $horario_fim) {
        list($h1, $m1) = array_pad(explode(':', $horario_inicio), 2, '0');
        list($h2, $m2) = array_pad(explode(':', $horario_fim), 2, '0');
        return [(int) $h1 * 60 + (int) $m1, (int) $h2 * 60 + (int) $m2];
    }

    /**
     * Refuses a pending triage during the analysis (técnico de enfermagem)
     */
    public function recusar_triagem($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Requisição inválida (token de segurança)');
            header('Location: ' . BASE_URL . '/tecnicoenfermagem/triagens');
            exit;
        }

        if (!RBAC::can(Constants::PERM_TRIAGEM_ANALISAR)) {
            Session::flash('error', 'Você não tem permissão para analisar triagens.');
            header('Location: ' . BASE_URL . '/tecnicoenfermagem/index');
            exit;
        }

        $id_triagem = $id ? (int) $id : (int) ($_POST['id_triagem'] ?? 0);
        $justificativa = trim($_POST['justificativa'] ?? '');

        if (!$id_triagem || !$this->profissionalModel->triagemPodeSerAnalisada($id_triagem)) {
            Session::flash('error', 'Triagem não localizada ou já encaminhada.');
            header('Location: ' . BASE_URL . '/tecnicoenfermagem/triagens');
            exit;
        }

        $this->pacienteModel->recusarTriagem($id_triagem, $justificativa !== '' ? $justificativa : null);

        Logger::audit(Constants::AUDITORIA_ACAO_RECUSAR_TRIAGEM, [
            'id_triagem' => $id_triagem,
            'justificativa' => $justificativa
        ]);

        (new Auditoria())->registrar(
            Constants::AUDITORIA_ACAO_RECUSAR_TRIAGEM,
            'triagem recusada na análise do técnico de enfermagem',
            ['id_triagem' => $id_triagem, 'justificativa' => $justificativa]
        );

        Session::flash('success', 'Triagem recusada.');
        header('Location: ' . BASE_URL . '/tecnicoenfermagem/triagens');
        exit;
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

        require_once __DIR__ . '/../views/tecnico_enfermagem/index.php';
    }

    public function minha_agenda() {
        $id_usuario = Session::get('usuario_id');
        $id_profissional = $this->profissionalModel->getIdByUsuario($id_usuario);

        $agendamentos_futuros = [];
        if ($id_profissional) {
            $agendamentos_futuros = $this->profissionalModel->getAgendamentosFuturos($id_profissional);
        }

        require_once __DIR__ . '/../views/tecnico_enfermagem/minha_agenda.php';
    }

    public function historico() {
        $id_usuario = Session::get('usuario_id');
        $id_profissional = $this->profissionalModel->getIdByUsuario($id_usuario);

        $visitas_concluidas = [];
        if ($id_profissional) {
            $visitas_concluidas = $this->profissionalModel->getVisitasConcluidas($id_profissional);
        }

        require_once __DIR__ . '/../views/tecnico_enfermagem/historico.php';
    }
}
