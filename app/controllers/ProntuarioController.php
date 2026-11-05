<?php
/**
 * ProntuarioController
 * Prontuario do paciente (historico append-only por secao).
 *
 * O roteador (public/index.php) mapeia a URL para este controller:
 *   /prontuario/buscar                  -> buscar()
 *   /prontuario/dados/{idPaciente}       -> dados($idPaciente)
 *   /prontuario/secao/{idPaciente}/{secao} -> secao($idPaciente, $secao)
 *   /prontuario/salvar_dados/{id}        -> salvar_dados($id)
 *   /prontuario/registrar/{id}          -> registrar($id)
 *   /prontuario/editar_registro/{id}    -> editar_registro($id)
 *   /prontuario/salvar_registro/{id}    -> salvar_registro($id)
 *   /prontuario/finalizar_registro/{id} -> finalizar_registro($id)
 *   /prontuario/excluir_registro/{id}   -> excluir_registro($id)
 */
class ProntuarioController {
    private $pacienteModel;
    private $registroModel;
    private $profissionalModel;
    private $enderecoModel;
    private $telefoneModel;
    private $usuarioModel;
    private $auditoria;

    private const PERMISSOES_PERFIL = [
        Constants::PERM_PRONTUARIO_PACIENTE_ACESSAR,
        Constants::PERM_PRONTUARIO_PACIENTE_REGISTRAR,
    ];

    public function __construct() {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }
        if (!RBAC::can(Constants::PERM_PRONTUARIO_PACIENTE_ACESSAR)) {
            header('Location: ' . BASE_URL . '/painel/index');
            exit;
        }

        $this->pacienteModel     = new Paciente();
        $this->registroModel     = new ProntuarioRegistro();
        $this->profissionalModel = new Profissional();
        $this->enderecoModel     = new Endereco();
        $this->telefoneModel     = new Telefone();
        $this->usuarioModel      = new Usuario();
        $this->auditoria         = new Auditoria();
    }

    private function ehAjax(): bool {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    private function responderJson(array $dados): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Carrega a linha de tbl_paciente e a conta como paciente logado.
     */
    private function dadosDoPaciente(int $id): array {
        $paciente = $this->pacienteModel->getById($id);
        if (!$paciente) {
            Session::flash('error', 'Paciente nao localizado.');
            header('Location: ' . BASE_URL . '/prontuario/buscar');
            exit;
        }
        $usuario = $this->usuarioModel->find($paciente['fk_usuario']);
        if (!$usuario) {
            Session::flash('error', 'Usuario do paciente nao localizado.');
            header('Location: ' . BASE_URL . '/prontuario/buscar');
            exit;
        }
        return [$paciente, $usuario];
    }

    private function registrarAuditoria(string $acao, string $descricao, array $props = []): void {
        $this->auditoria->registrar($acao, $descricao, $props);
    }

    // ------------------------------------------------------------
    // BUSCA
    // ------------------------------------------------------------

    /**
     * Tela inicial: busca o paciente por nome ou CPF.
     *
     * A busca acontece AQUI, no controller, e o resultado vai para a
     * view em $resultados. Nao faca a consulta dentro da view: assim o
     * PHP valida uma vez so e a view nao depende de nenhuma variavel
     * que o controller NAO tenha criado.
     */
    public function buscar() {
        $termo = trim((string)($_GET['termo'] ?? ''));
        $resultados = [];
        if (mb_strlen($termo) >= 2) {
            $resultados = $this->usuarioModel->buscarPorNomeOuCpfPaciente($termo);
        }

        require_once __DIR__ . '/../views/prontuario/buscar.php';
    }

    // ------------------------------------------------------------
    // PAGINA 1 - DADOS DO PACIENTE (leitura)
    // ------------------------------------------------------------

    public function dados($id) {
        [$paciente, $usuario] = $this->dadosDoPaciente((int)$id);

        $endereco = $this->enderecoModel->doUsuario($paciente['fk_usuario']);
        $telefones = $this->telefoneModel->doUsuario($paciente['fk_usuario']);
        $anamnese = $this->pacienteModel->getAnamnese($paciente['id']);

        $contadores = $this->registroModel->contarPorSecao($paciente['id']);

        $this->registrarAuditoria(
            Constants::AUDITORIA_ACAO_CONSULTAR_PRONTUARIO,
            'Abriu o prontuario (dados do paciente) de ' . $usuario['nome'],
            ['paciente_id' => $paciente['id'], 'secao' => 'dados']
        );

        require_once __DIR__ . '/../views/prontuario/dados.php';
    }

    /**
     * Salva o que e editavel na pagina 1: cpf, rg, sexo, estado civil,
     * telefone e endereco (com os campos que a busca de CEP preenche).
     */
    public function salvar_dados($id) {
        $id = (int)$id;
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Token de seguranca invalido.');
            header('Location: ' . BASE_URL . '/prontuario/dados/' . $id);
            exit;
        }
        if (!RBAC::can(Constants::PERM_PRONTUARIO_PACIENTE_REGISTRAR)) {
            Session::flash('error', 'Voce nao tem permissao para alterar o prontuario.');
            header('Location: ' . BASE_URL . '/prontuario/dados/' . $id);
            exit;
        }

        [$paciente, $usuario] = $this->dadosDoPaciente($id);

        $cpf = preg_replace('/\D/', '', (string)($_POST['cpf'] ?? ''));
        if ($cpf !== '' && strlen($cpf) !== 11) {
            // tbl_paciente.cpf e CHAR(11). Gravar "123456" truncaria
            // o dado e o UNIQUE nao protegeria ninguem.
            Session::flash('error', 'O CPF deve ter 11 digitos.');
            header('Location: ' . BASE_URL . '/prontuario/dados/' . $id);
            exit;
        }
        if ($cpf !== '' && !Validator::isValidCpf($cpf)) {
            // Autenticidade: valida os dois digitos verificadores antes de gravar.
            Session::flash('error', 'CPF invalido: confira os 11 digitos.');
            header('Location: ' . BASE_URL . '/prontuario/dados/' . $id);
            exit;
        }
        if ($cpf !== '') {
            $outro = $this->dbCpfEmUso($cpf, $id);
            if ($outro) {
                Session::flash('error', 'Este CPF ja pertence a outro paciente.');
                header('Location: ' . BASE_URL . '/prontuario/dados/' . $id);
                exit;
            }
        }

        // A anamnese de cadastro (tbl_anamnese) NAO e editavel nesta tela.
        // Como Paciente::atualizar() regrava tbl_anamnese inteira, mandamos
        // os valores atuais junto: sem isto a ficha de cadastro seria
        // apagada (viraria NULL) no primeiro "Salvar dados".
        $anamneseAtual = $this->pacienteModel->getAnamnese($id);

        // As chaves planas sao as mesmas que o buscar_cep.js preenche e
        // as que Paciente::atualizar() ja sabe sincronizar.
        $this->pacienteModel->atualizar($id, [
            'cpf'                  => $cpf !== '' ? $cpf : null,
            'rg'                   => trim((string)($_POST['rg'] ?? '')),
            'sexo'                 => $_POST['sexo'] ?? Constants::PACIENTE_SEXO_NAO_INFORMADO,
            'estado_civil'         => $_POST['estado_civil'] ?? Constants::PACIENTE_ESTADO_NAO_INFORMADO,
            'telefone_principal'   => preg_replace('/\D/', '', (string)($_POST['telefone_principal'] ?? '')),
            'endereco_cep'         => preg_replace('/\D/', '', (string)($_POST['endereco_cep'] ?? '')),
            'endereco_rua'         => trim((string)($_POST['endereco_rua'] ?? '')),
            'endereco_numero'      => trim((string)($_POST['endereco_numero'] ?? '')),
            'endereco_complemento' => trim((string)($_POST['endereco_complemento'] ?? '')),
            'endereco_bairro'      => trim((string)($_POST['endereco_bairro'] ?? '')),
            'endereco_cidade'      => trim((string)($_POST['endereco_cidade'] ?? '')),
            'endereco_uf'          => strtoupper(substr((string)($_POST['endereco_uf'] ?? ''), 0, 2)),
            'alergias'             => $anamneseAtual['alergias'] ?? null,
            'doencas_previas'      => $anamneseAtual['doencas_previas'] ?? null,
            'medicamentos_uso'     => $anamneseAtual['medicamentos_uso'] ?? null,
            'cirurgias'            => $anamneseAtual['cirurgias'] ?? null,
            'observacoes'          => $anamneseAtual['observacoes'] ?? null,
        ]);

        $this->registrarAuditoria(
            Constants::AUDITORIA_ACAO_EDITAR_PRONTUARIO,
            'Atualizou os dados cadastrais do prontuario de ' . $usuario['nome'],
            ['paciente_id' => $id]
        );

        Session::flash('success', 'Dados do paciente atualizados.');
        header('Location: ' . BASE_URL . '/prontuario/dados/' . $id);
        exit;
    }

    private function dbCpfEmUso(string $cpf, int $ignorarId): bool {
        // tbl_paciente tem UNIQUE KEY (cpf): se o mesmo CPF entrasse em
        // dois pacientes, o MySQL recusaria com o erro 1062. Checamos
        // antes para devolver uma mensagem em portugues, em vez de um
        // erro tecnico na tela.
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT id FROM tbl_paciente WHERE cpf = :cpf AND id <> :id LIMIT 1"
        );
        $stmt->execute(['cpf' => $cpf, 'id' => $ignorarId]);
        return (bool)$stmt->fetchColumn();
    }

    // ------------------------------------------------------------
    // PAGINAS 2, 3 e 4 - HISTORICO POR SECAO
    // ------------------------------------------------------------

    private function secoesValidas(): array {
        return array_keys(ProntuarioRegistro::SECOES);
    }

    public function secao($id, $secao) {
        $id = (int)$id;
        $secao = (string)$secao;
        if (!in_array($secao, $this->secoesValidas(), true)) {
            Session::flash('error', 'Secao invalida.');
            header('Location: ' . BASE_URL . '/prontuario/dados/' . $id);
            exit;
        }

        [$paciente, $usuario] = $this->dadosDoPaciente($id);

        $registros = $this->registroModel->listarPorSecao($id, $secao);
        $contadores = $this->registroModel->contarPorSecao($id);
        $total = count($registros);

        $this->registrarAuditoria(
            Constants::AUDITORIA_ACAO_CONSULTAR_PRONTUARIO,
            'Abriu o prontuario (' . ProntuarioRegistro::SECOES[$secao] . ') de ' . $usuario['nome'],
            ['paciente_id' => $id, 'secao' => $secao]
        );

        require_once __DIR__ . '/../views/prontuario/secao.php';
    }

    /**
     * Cria um lancamento na secao pedida.
     */
    public function registrar($id) {
        $id = (int)$id;
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Token de seguranca invalido.');
            header('Location: ' . BASE_URL . '/prontuario/dados/' . $id);
            exit;
        }
        if (!RBAC::can(Constants::PERM_PRONTUARIO_PACIENTE_REGISTRAR)) {
            Session::flash('error', 'Voce nao tem permissao para registrar no prontuario.');
            header('Location: ' . BASE_URL . '/prontuario/dados/' . $id);
            exit;
        }

        [$paciente, $usuario] = $this->dadosDoPaciente($id);

        $secao = (string)($_POST['secao'] ?? '');
        if (!in_array($secao, $this->secoesValidas(), true)) {
            Session::flash('error', 'Secao invalida.');
            header('Location: ' . BASE_URL . '/prontuario/dados/' . $id);
            exit;
        }

        $conteudo = trim((string)($_POST['conteudo'] ?? ''));
        if (mb_strlen($conteudo) < 10) {
            Session::flash('error', 'Escreva ao menos 10 caracteres no registro.');
            header('Location: ' . BASE_URL . '/prontuario/secao/' . $id . '/' . $secao);
            exit;
        }

        $status = ($_POST['status'] ?? '') === Constants::PRONTUARIO_STATUS_RASCUNHO
            ? Constants::PRONTUARIO_STATUS_RASCUNHO
            : Constants::PRONTUARIO_STATUS_FINALIZADO;

        // >>> AUTOR x PACIENTE: cuidado aqui.
        // $usuario (acima) e o USUARIO DO PACIENTE - e ele quem e
        // passageiro da URL. O AUTOR do lancamento e quem esta
        // logado. Sao duas pessoas diferentes na maioria das vezes:
        // se voce gravar $usuario['id'] em fk_usuario, o prontuario
        // passa a dizer que o proprio paciente escreveu a evolucao.
        $autorId = (int)Session::get('usuario_id');
        if (!$autorId) {
            Session::flash('error', 'Sessao expirada. Entre novamente.');
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }
        $profissionalId = $this->profissionalModel->getIdByUsuario($autorId);
        $agendamentoId = (int)($_POST['fk_agendamento'] ?? 0);

        // Data clinica opcional: por default e "agora" (o DEFAULT do
        // banco). Se o medico informar, gravamos a data do atendimento
        // - e para isso que data_hora e DATETIME e nao TIMESTAMP.
        $dataHora = null;
        $informada = trim((string)($_POST['data_hora'] ?? ''));
        if ($informada !== '') {
            $ts = strtotime($informada);
            if ($ts === false) {
                Session::flash('error', 'Data do atendimento invalida.');
                header('Location: ' . BASE_URL . '/prontuario/secao/' . $id . '/' . $secao);
                exit;
            }
            $dataHora = date('Y-m-d H:i:s', $ts);
        }

        $ok = $this->registroModel->criar([
            'fk_paciente'     => $id,
            'fk_usuario'      => $autorId,
            'fk_profissional' => $profissionalId ?: null,
            'fk_agendamento'  => $agendamentoId ?: null,
            'secao'           => $secao,
            'conteudo'        => $conteudo,
            'status'          => $status,
            'data_hora'       => $dataHora,
        ]);

        if ($ok && $this->ehAjax()) {
            $this->responderJson([
                'success' => true,
                'registro' => $this->registroModel->getById($this->registroModel->ultimoId()),
                'contadores' => $this->registroModel->contarPorSecao($id),
            ]);
        }

        if ($ok) {
            $this->registrarAuditoria(
                Constants::AUDITORIA_ACAO_REGISTRAR_PRONTUARIO,
                'Registrou em ' . ProntuarioRegistro::SECOES[$secao] . ' no prontuario de ' . $usuario['nome'],
                ['paciente_id' => $id, 'secao' => $secao, 'status' => $status]
            );
            Session::flash('success', $status === Constants::PRONTUARIO_STATUS_RASCUNHO
                ? 'Rascunho salvo. Voce pode editar antes de finalizar.'
                : 'Registro adicionado ao prontuario.');
        } else {
            Session::flash('error', 'Nao foi possivel salvar o registro.');
        }

        header('Location: ' . BASE_URL . '/prontuario/secao/' . $id . '/' . $secao);
        exit;
    }

    // ------------------------------------------------------------
    // RASCUNHO: editar, finalizar, excluir
    // ------------------------------------------------------------

    private function ehMeuRascunho(array $registro, int $usuarioId): bool {
        return (int)$registro['fk_usuario'] === $usuarioId
            && $registro['status'] === Constants::PRONTUARIO_STATUS_RASCUNHO;
    }

    public function editar_registro($idRegistro) {
        $registro = $this->registroModel->getById((int)$idRegistro);
        if (!$registro) {
            Session::flash('error', 'Registro nao localizado.');
            header('Location: ' . BASE_URL . '/prontuario/buscar');
            exit;
        }
        if (!$this->ehMeuRascunho($registro, (int)Session::get('usuario_id'))) {
            Session::flash('error', 'So o autor pode editar um rascunho.');
            header('Location: ' . BASE_URL . '/prontuario/secao/'
                . $registro['fk_paciente'] . '/' . $registro['secao']);
            exit;
        }
        [$paciente, $usuario] = $this->dadosDoPaciente((int)$registro['fk_paciente']);

        require_once __DIR__ . '/../views/prontuario/editar.php';
    }

    public function salvar_registro($idRegistro) {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Token de seguranca invalido.');
            header('Location: ' . BASE_URL . '/prontuario/buscar');
            exit;
        }
        $registro = $this->registroModel->getById((int)$idRegistro);
        if (!$registro) {
            Session::flash('error', 'Registro nao localizado.');
            header('Location: ' . BASE_URL . '/prontuario/buscar');
            exit;
        }
        if (!$this->ehMeuRascunho($registro, (int)Session::get('usuario_id'))) {
            Session::flash('error', 'So o autor pode editar um rascunho.');
            header('Location: ' . BASE_URL . '/prontuario/secao/'
                . $registro['fk_paciente'] . '/' . $registro['secao']);
            exit;
        }

        $conteudo = trim((string)($_POST['conteudo'] ?? ''));
        if (mb_strlen($conteudo) < 10) {
            Session::flash('error', 'Escreva ao menos 10 caracteres no registro.');
            header('Location: ' . BASE_URL . '/prontuario/editar_registro/' . $idRegistro);
            exit;
        }

        $this->registroModel->atualizarRascunho((int)$idRegistro, $conteudo);

        $this->registrarAuditoria(
            Constants::AUDITORIA_ACAO_EDITAR_PRONTUARIO,
            'Editou um rascunho no prontuario',
            ['registro_id' => (int)$idRegistro, 'paciente_id' => (int)$registro['fk_paciente']]
        );

        Session::flash('success', 'Rascunho atualizado.');
        header('Location: ' . BASE_URL . '/prontuario/secao/'
            . $registro['fk_paciente'] . '/' . $registro['secao']);
        exit;
    }

    public function finalizar_registro($idRegistro) {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Token de seguranca invalido.');
            header('Location: ' . BASE_URL . '/prontuario/buscar');
            exit;
        }
        $registro = $this->registroModel->getById((int)$idRegistro);
        if (!$registro) {
            Session::flash('error', 'Registro nao localizado.');
            header('Location: ' . BASE_URL . '/prontuario/buscar');
            exit;
        }
        if (!$this->ehMeuRascunho($registro, (int)Session::get('usuario_id'))) {
            Session::flash('error', 'So o autor pode finalizar um rascunho.');
            header('Location: ' . BASE_URL . '/prontuario/secao/'
                . $registro['fk_paciente'] . '/' . $registro['secao']);
            exit;
        }

        $this->registroModel->finalizar((int)$idRegistro);

        $this->registrarAuditoria(
            Constants::AUDITORIA_ACAO_FINALIZAR_PRONTUARIO,
            'Finalizou um registro no prontuario',
            ['registro_id' => (int)$idRegistro, 'paciente_id' => (int)$registro['fk_paciente']]
        );

        Session::flash('success', 'Registro finalizado. Agora ele nao pode ser alterado.');
        header('Location: ' . BASE_URL . '/prontuario/secao/'
            . $registro['fk_paciente'] . '/' . $registro['secao']);
        exit;
    }

    public function excluir_registro($idRegistro) {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Token de seguranca invalido.');
            header('Location: ' . BASE_URL . '/prontuario/buscar');
            exit;
        }
        $registro = $this->registroModel->getById((int)$idRegistro);
        if (!$registro) {
            Session::flash('error', 'Registro nao localizado.');
            header('Location: ' . BASE_URL . '/prontuario/buscar');
            exit;
        }
        if (!$this->ehMeuRascunho($registro, (int)Session::get('usuario_id'))) {
            Session::flash('error', 'So o autor pode excluir um rascunho.');
            header('Location: ' . BASE_URL . '/prontuario/secao/'
                . $registro['fk_paciente'] . '/' . $registro['secao']);
            exit;
        }

        $this->registroModel->excluir((int)$idRegistro);

        $this->registrarAuditoria(
            Constants::AUDITORIA_ACAO_EXCLUIR_PRONTUARIO,
            'Excluiu um rascunho do prontuario',
            ['registro_id' => (int)$idRegistro, 'paciente_id' => (int)$registro['fk_paciente']]
        );

        Session::flash('success', 'Rascunho excluido.');
        header('Location: ' . BASE_URL . '/prontuario/secao/'
            . $registro['fk_paciente'] . '/' . $registro['secao']);
        exit;
    }
}
