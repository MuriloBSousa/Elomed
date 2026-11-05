<?php
class PacienteController {
    private $usuarioModel;
    private $pacienteModel;
    private $agendamentoModel;
    
    public function __construct() {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }
        
        if (!RBAC::can(Constants::PERM_PACIENTE_ACESSAR)) {
            header('Location: ' . BASE_URL . '/painel/index');
            exit;
        }
        
        $this->usuarioModel = new Usuario();
        $this->pacienteModel = new Paciente();
        $this->agendamentoModel = new Agendamento();
    }
    
    public function index() {
        $id_usuario = Session::get('usuario_id');
        $id_paciente = $this->pacienteModel->getIdByUsuario($id_usuario);

        $ultima_triagem = null;
        if ($id_paciente) {
            $ultima_triagem = $this->pacienteModel->getUltimaTriagem($id_paciente);
        }

        $proximo_agendamento = null;
        if ($id_paciente) {
            $proximo_agendamento = $this->pacienteModel->getProximoAgendamento($id_paciente);
        }

        $agendamentos_futuros = [];
        if ($id_paciente) {
            $agendamentos_futuros = $this->pacienteModel->getAgendamentosFuturos($id_paciente);
        }

        $foto_paciente = '';
        if ($id_paciente) {
            $foto_paciente = $this->pacienteModel->getFoto($id_paciente);
        }

        $cadastro_incompleto = !$id_paciente;

        require_once __DIR__ . '/../views/paciente/index.php';
    }

    public function completar_cadastro() {
        $id_usuario = Session::get('usuario_id');
        $id_paciente = $this->pacienteModel->getIdByUsuario($id_usuario);
        $dados = $id_paciente ? $this->pacienteModel->getByUsuario($id_usuario) : [];
        $foto_atual = !empty($dados['foto']) ? $dados['foto'] : '';

        require_once __DIR__ . '/../views/paciente/completar_cadastro.php';
    }

    public function salvar_completar_cadastro() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/paciente/completar_cadastro');
            exit;
        }

        // Validate CSRF
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Token de segurança inválido');
            header('Location: ' . BASE_URL . '/paciente/completar_cadastro');
            exit;
        }

        $id_usuario = Session::get('usuario_id');
        $id_paciente = $this->pacienteModel->getIdByUsuario($id_usuario);

        // Validate inputs
        $validator = new Validator($_POST);
        $validator->required('cpf')
                  ->cpf('cpf')
                  ->required('telefone_principal')
                  ->required('data_nascimento')
                  ->dateNascimento('data_nascimento')
                  ->date('convenio_validade');

        if ($validator->fails()) {
            Session::flash('error', $validator->getFirstError());
            header('Location: ' . BASE_URL . '/paciente/completar_cadastro');
            exit;
        }

        $data = $validator->getData();

        // Patient photo
        $foto = $id_paciente ? ($this->pacienteModel->getFoto($id_paciente) ?: '') : '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $arquivo = $_FILES['foto'];
            $extensaoBase = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

            if ($arquivo['error'] !== UPLOAD_ERR_OK) {
                Session::flash('error', 'Erro no envio da foto. Tente novamente.');
                header('Location: ' . BASE_URL . '/paciente/completar_cadastro');
                exit;
            }
            if (!in_array($extensaoBase, $extensoesPermitidas, true)) {
                Session::flash('error', 'Formato de imagem inválido. Use JPG, PNG ou WEBP.');
                header('Location: ' . BASE_URL . '/paciente/completar_cadastro');
                exit;
            }
            if ($arquivo['size'] > 2 * 1024 * 1024) {
                Session::flash('error', 'A foto deve ter no máximo 2MB.');
                header('Location: ' . BASE_URL . '/paciente/completar_cadastro');
                exit;
            }

            $extensao = $extensaoBase === 'jpeg' ? 'jpg' : $extensaoBase;
            $nomeArquivo = 'pac_' . (int) $id_usuario . '_' . time() . '.' . $extensao;
            $destinoAbsoluto = dirname(__DIR__, 2) . '/public/Assets/images/usuario/' . $nomeArquivo;

            if (!is_dir(dirname($destinoAbsoluto))) {
                mkdir(dirname($destinoAbsoluto), 0777, true);
            }

            if (!move_uploaded_file($arquivo['tmp_name'], $destinoAbsoluto)) {
                Session::flash('error', 'Não foi possível salvar a foto do paciente.');
                header('Location: ' . BASE_URL . '/paciente/completar_cadastro');
                exit;
            }

            // Remove the old photo
            if ($foto && file_exists(dirname(__DIR__, 2) . '/public/' . $foto)) {
                @unlink(dirname(__DIR__, 2) . '/public/' . $foto);
            }

            $foto = 'Assets/images/usuario/' . $nomeArquivo;
        }

        $dados = [
            'cpf'                  => preg_replace('/\D/', '', $data['cpf']),
            'rg'                   => $data['rg'] ?? '',
            'telefone_principal'   => preg_replace('/\D/', '', $data['telefone_principal']),
            'sexo'                 => $data['sexo'] ?? '',
            'estado_civil'         => $data['estado_civil'] ?? '',
            'endereco_rua'         => $data['endereco_rua'] ?? '',
            'endereco_numero'      => $data['endereco_numero'] ?? '',
            'endereco_complemento' => $data['endereco_complemento'] ?? '',
            'endereco_bairro'      => $data['endereco_bairro'] ?? '',
            'endereco_cidade'      => $data['endereco_cidade'] ?? '',
            'endereco_uf'          => $data['endereco_uf'] ?? '',
            'endereco_cep'         => preg_replace('/\D/', '', $data['endereco_cep'] ?? ''),
            'contato_nome'         => $data['contato_nome'] ?? '',
            'contato_parentesco'   => $data['contato_parentesco'] ?? '',
            'contato_telefone'     => preg_replace('/\D/', '', $data['contato_telefone'] ?? ''),
            'convenio_operadora'   => $data['convenio_operadora'] ?? '',
            'convenio_carteirinha' => $data['convenio_carteirinha'] ?? '',
            'convenio_validade'    => $data['convenio_validade'] ?? null,
            'foto'                 => $foto,
            'data_nascimento'      => $data['data_nascimento'],
            'contato_emergencia'   => preg_replace('/\D/', '', $data['tel_emergencia'] ?? ''),
            'alergias'             => $data['alergias'] ?? '',
            'doencas_previas'      => $data['doencas_previas'] ?? '',
            'medicamentos_uso'     => $data['medicamentos_uso'] ?? '',
            'cirurgias'            => $data['cirurgias'] ?? ''
        ];

        try {
            if ($id_paciente) {
                $resultado = $this->pacienteModel->atualizar($id_paciente, $dados);
            } else {
                $dados['fk_usuario'] = $id_usuario;
                $resultado = $this->pacienteModel->criar($dados);
            }

            if ($resultado) {
                Logger::audit(Constants::AUDITORIA_ACAO_ATUALIZAR_USUARIO, [
                    'tipo' => 'paciente',
                    'fk_usuario' => $id_usuario
                ]);
                (new Auditoria())->registrar(
                    Constants::AUDITORIA_ACAO_ATUALIZAR_USUARIO,
                    'paciente completou o cadastro',
                    ['fk_usuario' => $id_usuario]
                );
                Session::flash('success', 'Cadastro de paciente salvo com sucesso!');
            } else {
                Session::flash('error', 'Erro ao salvar o cadastro.');
            }
        } catch (DatabaseException $e) {
            Logger::error("Erro ao completar cadastro de paciente", ['error' => $e->getMessage()]);
            Session::flash('error', 'Erro ao salvar o cadastro. Tente novamente.');
        }

        header('Location: ' . BASE_URL . '/paciente/completar_cadastro');
        exit;
    }

    public function minhas_triagens() {
        $id_usuario = Session::get('usuario_id');
        $id_paciente = $this->pacienteModel->getIdByUsuario($id_usuario);

        $triagens = [];
        if ($id_paciente) {
            $triagens = $this->pacienteModel->getTriagens($id_paciente);
        }

        require_once __DIR__ . '/../views/paciente/minhas_triagens.php';
    }

    public function processar_triagem() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/paciente/minhas_triagens');
            exit;
        }

        // Validate CSRF
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Token de segurança inválido');
            header('Location: ' . BASE_URL . '/paciente/minhas_triagens');
            exit;
        }

        $id_usuario = Session::get('usuario_id');
        $id_paciente = $this->pacienteModel->getIdByUsuario($id_usuario);

        if (!$id_paciente) {
            Session::flash('error', 'Paciente não encontrado');
            header('Location: ' . BASE_URL . '/paciente/minhas_triagens');
            exit;
        }

        $nome_solicitante = trim($_POST['nome_solicitante'] ?? '');
        $queixa_principal = trim($_POST['queixa_principal'] ?? '');
        $descricao_sintomas = trim($_POST['descricao_sintomas'] ?? '');
        $data_inicio_sintomas = trim($_POST['data_inicio_sintomas'] ?? '');
        // The patient does not self-classify: the risk (pulseira) is defined
        // by the IA analysis and/or the professional review.
        $classificacao = null;

        if (empty($nome_solicitante)) {
            Session::flash('error', 'Nome do solicitante é obrigatório');
            header('Location: ' . BASE_URL . '/paciente/minhas_triagens');
            exit;
        }

        if (empty($queixa_principal)) {
            Session::flash('error', 'Informe a queixa principal para a triagem');
            header('Location: ' . BASE_URL . '/paciente/minhas_triagens');
            exit;
        }

        if ($data_inicio_sintomas !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_inicio_sintomas)) {
            Session::flash('error', 'Data de início dos sintomas inválida');
            header('Location: ' . BASE_URL . '/paciente/minhas_triagens');
            exit;
        }

        $id_triagem = $this->pacienteModel->criarTriagem(
            $id_paciente,
            $nome_solicitante,
            $classificacao,
            $queixa_principal,
            $descricao_sintomas !== '' ? $descricao_sintomas : null,
            $data_inicio_sintomas !== '' ? $data_inicio_sintomas : null
        );

        if ($id_triagem) {
            $this->registrarAvaliacaoInicialIA(
                $id_triagem,
                $id_paciente,
                [
                    'nome_solicitante' => $nome_solicitante,
                    'queixa_principal' => $queixa_principal,
                    'descricao_sintomas' => $descricao_sintomas,
                    'data_inicio_sintomas' => $data_inicio_sintomas
                ]
            );

            Logger::audit(Constants::AUDITORIA_ACAO_CRIAR_TRIAGEM, [
                'id_paciente' => $id_paciente,
                'nome_solicitante' => $nome_solicitante,
                'queixa_principal' => $queixa_principal
            ]);

            (new Auditoria())->registrar(
                Constants::AUDITORIA_ACAO_CRIAR_TRIAGEM,
                'triagem solicitada',
                ['id_paciente' => $id_paciente, 'classificacao' => $classificacao ?? null, 'queixa_principal' => $queixa_principal]
            );

            Session::flash('success', 'Triagem solicitada com sucesso');
        } else {
            Session::flash('error', 'Erro ao solicitar triagem');
        }

        header('Location: ' . BASE_URL . '/paciente/minhas_triagens');
        exit;
    }

    public function agendamento() {
        $id_usuario = Session::get('usuario_id');
        $id_paciente = $this->pacienteModel->getIdByUsuario($id_usuario);

        $agendamentos = [];
        if ($id_paciente) {
            $agendamentos = $this->pacienteModel->getAgendamentos($id_paciente);
        }

        require_once __DIR__ . '/../views/paciente/agendamento.php';
    }

    public function historico() {
        $id_usuario = Session::get('usuario_id');
        $id_paciente = $this->pacienteModel->getIdByUsuario($id_usuario);

        $visitas = [];
        $avaliaveis = [];
        $jaAvaliados = [];
        if ($id_paciente) {
            $visitas = $this->pacienteModel->getVisitasConcluidas($id_paciente);

            $feedbackModel = new Feedback();
            $avaliaveis = $feedbackModel->listarAvaliaveisPorPaciente($id_paciente);
            $jaAvaliados = $feedbackModel->listarAvaliadosPorPaciente($id_paciente);
        }

        require_once __DIR__ . '/../views/paciente/historico.php';
    }

    public function linha_do_tempo() {
        $id_usuario = Session::get('usuario_id');
        $id_paciente = $this->pacienteModel->getIdByUsuario($id_usuario);
        if (!$id_paciente) {
            Session::flash('error', 'Registro de paciente nao localizado');
            header('Location: ' . BASE_URL . '/paciente/index');
            exit;
        }
        $linha = $this->pacienteModel->getLinhaDoTempo($id_paciente);
        require_once __DIR__ . '/../views/paciente/linha_do_tempo.php';
    }

    /**
     * Reschedules one of the patient's own appointments (bounded reschedules).
     */
    public function reagendar_agendamento($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Requisição inválida (token de segurança)');
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }

        $id_agendamento = $id ? (int) $id : (int) ($_POST['id'] ?? 0);
        $id_usuario = Session::get('usuario_id');
        $id_paciente = $this->pacienteModel->getIdByUsuario($id_usuario);

        if (!$id_agendamento || !$id_paciente) {
            Session::flash('error', 'Agendamento ou paciente inválido.');
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }

        $ag = $this->agendamentoModel->getById($id_agendamento);
        if (!$ag || (int) $ag['fk_paciente'] !== (int) $id_paciente) {
            Session::flash('error', 'Este agendamento não pertence ao seu cadastro.');
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }

        if (!in_array($ag['status'], [Constants::AGENDAMENTO_PENDENTE, Constants::AGENDAMENTO_AGENDADO], true)) {
            Session::flash('error', 'Só é possível reagendar consultas pendentes ou confirmadas.');
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }

        // Visits originated from a triage are scheduled by the técnico de
        // enfermagem, so the patient cannot propose a new date/time for them.
        if (!empty($ag['fk_triagem'])) {
            Session::flash('error', 'Esta visita foi agendada pela equipe de enfermagem a partir da sua triagem.');
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }

        if ((int) ($ag['vezes_reagendado'] ?? 0) >= Constants::AGENDAMENTO_MAX_REAGENDAMENTOS) {
            Session::flash('error', 'Limite de reagendamentos atingido (' . Constants::AGENDAMENTO_MAX_REAGENDAMENTOS . '). Contate a equipe.');
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }

        $validator = new Validator($_POST);
        $validator->required('data')->date('data')
                  ->required('horario_inicio')
                  ->required('horario_fim');

        if ($validator->fails()) {
            Session::flash('error', $validator->getFirstError());
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }

        $data = $validator->getData();
        $novaData = $data['data'];
        $novoInicio = $data['horario_inicio'];
        $novoFim = $data['horario_fim'];

        if ($novaData < date('Y-m-d')) {
            Session::flash('error', 'A data do reagendamento nao pode estar no passado');
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }

        $startMin = (($p = explode(':', $novoInicio)) && count($p) === 2) ? ((int)$p[0] * 60 + (int)$p[1]) : 0;
        $endMin = (($p = explode(':', $novoFim)) && count($p) === 2) ? ((int)$p[0] * 60 + (int)$p[1]) : 0;
        if ($startMin <= 0 || $endMin <= 0 || $endMin <= $startMin) {
            Session::flash('error', 'O horário do fim precisa ser maior que o horário do início');
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }

        // Re-check conflicts for the (same) professional and the patient,
        // excluding the schedule being rescheduled from the overlap.
        if ($this->agendamentoModel->verificarConflito($ag['fk_profissional'], $novaData, $novoInicio, $novoFim, $id_agendamento)) {
            Session::flash('error', 'Esse profissional ja tem atendimento nesse horario');
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }
        if ($this->agendamentoModel->verificarConflitoPaciente($id_paciente, $novaData, $novoInicio, $novoFim, $id_agendamento)) {
            Session::flash('error', 'Voce ja tem um atendimento marcado nesse horario');
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }

        // Revalidate the professional's daily-visit limit and pause window.
        $limitesReag = $this->agendamentoModel->getLimitesProfissional($ag['fk_profissional']);
        if ($limitesReag && !empty($limitesReag['limite_diario'])
            && $this->agendamentoModel->contarAgendamentosDia($ag['fk_profissional'], $novaData) >= (int) $limitesReag['limite_diario']) {
            Session::flash('error', 'Limite de atendimentos do dia atingido para esse profissional');
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }
        if ($limitesReag && $this->agendamentoModel->conflitaComPausa($limitesReag, $novoInicio, $novoFim)) {
            Session::flash('error', 'Esse horario cai na pausa do profissional');
            header('Location: ' . BASE_URL . '/paciente/agendamento');
            exit;
        }

        if ($this->agendamentoModel->reagendar($id_agendamento, $novaData, $novoInicio, $novoFim)) {
            Logger::audit(Constants::AUDITORIA_ACAO_ATUALIZAR_AGENDAMENTO, [
                'id_agendamento' => $id_agendamento,
                'novo' => $novaData . ' ' . $novoInicio
            ]);

            (new Auditoria())->registrar(
                Constants::AUDITORIA_ACAO_ATUALIZAR_AGENDAMENTO,
                'agendamento reagendado',
                ['id_agendamento' => $id_agendamento, 'novo' => $novaData . ' ' . $novoInicio]
            );

            // Notificacao WhatsApp (Evolution API): best-effort, falha de
            // envio nunca invalida o reagendamento ja salvo.
            (new WhatsAppService())->enviarAvisoReagendamento(
                (int) $id_usuario,
                (string) ($this->pacienteModel->getById($id_paciente)['nome'] ?? ''),
                $novaData,
                $novoInicio,
                $novoFim
            );

            Session::flash('success', 'Agendamento reagendado. Um novo horário foi proposto para confirmação.');
        } else {
            Session::flash('error', 'Não foi possível reagendar. Tente novamente.');
        }

        header('Location: ' . BASE_URL . '/paciente/agendamento');
        exit;
    }

    /**
     * Registra a avaliação (0 a 5 estrelas) do atendimento concluído.
     * Só o paciente dono da visita pode avaliar, e apenas uma vez por atendimento.
     */
    public function avaliar_atendimento($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/paciente/historico');
            exit;
        }

        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Token de segurança inválido');
            header('Location: ' . BASE_URL . '/paciente/historico');
            exit;
        }

        if (!RBAC::can(Constants::PERM_FEEDBACK_REGISTRAR)) {
            Session::flash('error', 'Você não tem permissão para avaliar atendimentos.');
            header('Location: ' . BASE_URL . '/paciente/historico');
            exit;
        }

        $id_atendimento = $id ? (int) $id : (int) ($_POST['id_atendimento'] ?? 0);
        $nota = isset($_POST['nota']) ? (int) $_POST['nota'] : -1;
        $comentario = trim($_POST['comentario'] ?? '');

        if ($id_atendimento <= 0) {
            Session::flash('error', 'Atendimento inválido.');
            header('Location: ' . BASE_URL . '/paciente/historico');
            exit;
        }

        if ($nota < 0 || $nota > 5) {
            Session::flash('error', 'Dê uma nota de 0 a 5 estrelas.');
            header('Location: ' . BASE_URL . '/paciente/historico');
            exit;
        }

        if (mb_strlen($comentario) > 500) {
            Session::flash('error', 'O comentário deve ter no máximo 500 caracteres.');
            header('Location: ' . BASE_URL . '/paciente/historico');
            exit;
        }

        $id_usuario = Session::get('usuario_id');
        $id_paciente = $this->pacienteModel->getIdByUsuario($id_usuario);

        $feedbackModel = new Feedback();
        $atendimento = $feedbackModel->validarAvaliacao($id_atendimento, $id_paciente);

        if (!$atendimento) {
            Session::flash('error', 'Atendimento não localizado, ainda em aberto ou já avaliado.');
            header('Location: ' . BASE_URL . '/paciente/historico');
            exit;
        }

        $ok = $feedbackModel->registrar(
            $atendimento['id_atendimento'],
            $atendimento['fk_profissional'],
            $id_paciente,
            $id_usuario,
            $nota,
            $comentario !== '' ? $comentario : null
        );

        if (!$ok) {
            Session::flash('error', 'Este atendimento já foi avaliado.');
            header('Location: ' . BASE_URL . '/paciente/historico');
            exit;
        }

        Logger::audit(Constants::AUDITORIA_ACAO_REGISTRAR_FEEDBACK, [
            'id_atendimento' => $atendimento['id_atendimento'],
            'fk_profissional' => $atendimento['fk_profissional'],
            'nota' => $nota
        ]);

        (new Auditoria())->registrar(
            Constants::AUDITORIA_ACAO_REGISTRAR_FEEDBACK,
            'avaliação do atendimento registrada',
            [
                'id_atendimento' => $atendimento['id_atendimento'],
                'fk_profissional' => $atendimento['fk_profissional'],
                'nota' => $nota
            ]
        );

        Session::flash('success', 'Obrigado pela sua avaliação!');
        header('Location: ' . BASE_URL . '/paciente/historico');
        exit;
    }

    /**
     * Runs the preliminary AI assessment right after the triage is created and
     * stores it, so the técnico de enfermagem receives the triage already
     * carrying a suggested risk level, profile and professional.
     *
     * It is a recommendation, never a decision: any failure here (API down,
     * timeout, malformed answer) is swallowed and the triage simply reaches the
     * técnico without a recommendation, which is exactly how it worked before.
     */
    private function registrarAvaliacaoInicialIA($id_triagem, $id_paciente, array $dadosTriagem) {
        try {
            $profissionalModel = new Profissional();
            $iaModel = new IA();

            $candidatos = $profissionalModel->listarParaEncaminhamento(
                [Constants::PERFIL_MEDICO, Constants::PERFIL_ENFERMEIRO],
                [$dadosTriagem['queixa_principal'] ?? '', $dadosTriagem['descricao_sintomas'] ?? '']
            );

            if (empty($candidatos)) {
                Logger::warning('Avaliação inicial de IA ignorada: nenhum profissional ativo', [
                    'id_triagem' => $id_triagem
                ]);
                return;
            }

            $paciente = $this->pacienteModel->getById($id_paciente) ?: [];
            $anamnese = $this->pacienteModel->getAnamnese($id_paciente);

            $idadeSexo = $this->descreverIdadeSexo($paciente);

            $avaliacao = $iaModel->avaliarTriagemComProfissionais([
                'nome_solicitante' => $dadosTriagem['nome_solicitante'] ?? '',
                'queixa_principal' => $dadosTriagem['queixa_principal'] ?? '',
                'descricao_sintomas' => $dadosTriagem['descricao_sintomas'] ?? '',
                'data_inicio_sintomas' => $dadosTriagem['data_inicio_sintomas'] ?? '',
                'idade_sexo' => $idadeSexo,
                'alergias' => $anamnese['alergias'] ?? '',
                'doencas_previas' => $anamnese['doencas_previas'] ?? '',
                'medicamentos_uso' => $anamnese['medicamentos_uso'] ?? '',
                'cirurgias' => $anamnese['cirurgias'] ?? ''
            ], $candidatos);

            $this->pacienteModel->registrarAvaliacaoIA($id_triagem, $avaliacao);
            $iaModel->registrarUso(Session::get('usuario_id'));

            $sugerido = $profissionalModel->getSugeridoPelaIA($avaliacao['profissional_sugerido']);

            Logger::audit(Constants::AUDITORIA_ACAO_ANALISE_IA_ENCAMINHAMENTO, [
                'id_triagem' => $id_triagem,
                'ia_classificacao' => $avaliacao['classificacao'],
                'ia_perfil_recomendado' => $avaliacao['perfil_recomendado'],
                'ia_profissional_sugerido' => $avaliacao['profissional_sugerido'],
                'ia_profissional_nome' => $sugerido['nome_profissional'] ?? null,
                'ia_modelo' => $avaliacao['modelo'],
                'ia_sucesso' => $avaliacao['sucesso']
            ]);

            (new Auditoria())->registrar(
                Constants::AUDITORIA_ACAO_ANALISE_IA_ENCAMINHAMENTO,
                'avaliação prévia de IA gerada na solicitação de triagem',
                [
                    'id_triagem' => $id_triagem,
                    'classificacao' => $avaliacao['classificacao'],
                    'perfil_recomendado' => $avaliacao['perfil_recomendado'],
                    'profissional_sugerido' => $sugerido['nome_profissional'] ?? null,
                    'modelo' => $avaliacao['modelo']
                ]
            );
        } catch (Throwable $e) {
            Logger::error('Falha na avaliação prévia de IA da triagem', [
                'id_triagem' => $id_triagem,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Formats age and sex for the AI prompt.
     */
    private function descreverIdadeSexo(array $paciente) {
        $partes = [];

        if (!empty($paciente['data_nascimento'])) {
            $nascimento = \DateTime::createFromFormat('Y-m-d', $paciente['data_nascimento']);
            if ($nascimento) {
                $idade = (new \DateTime())->diff($nascimento)->y;
                $partes[] = $idade . ' ano(s)';
            }
        }

        if (!empty($paciente['sexo']) && $paciente['sexo'] !== 'nao_informado') {
            $rotulos = [
                'feminino' => 'feminino',
                'masculino' => 'masculino'
            ];
            $partes[] = $rotulos[$paciente['sexo']] ?? $paciente['sexo'];
        }

        return $partes ? implode(', ', $partes) : 'não informado';
    }
}
