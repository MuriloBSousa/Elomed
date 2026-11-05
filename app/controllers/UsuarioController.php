<?php
class UsuarioController {
    private $usuarioModel;
    private $profissionalModel;
    private $pacienteModel;

    public function __construct() {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        // Business rule: only users with the manage-users permission may manage users.
        if (!RBAC::can(Constants::PERM_USUARIO_GERENCIAR)) {
            header('Location: ' . BASE_URL . '/painel/index');
            exit;
        }

        $this->usuarioModel = new Usuario();
        $this->profissionalModel = new Profissional();
        $this->pacienteModel = new Paciente();
    }
        
    public function index() {
        $usuarios = $this->usuarioModel->listarTodos();
        require_once __DIR__ . '/../views/usuarios/index.php';
    }

    public function cadastrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF
            if (!CSRF::validateRequest()) {
                Session::flash('error', 'Token de segurança inválido');
                require_once __DIR__ . '/../views/usuarios/cadastrar.php';
                return;
            }

            // Validate inputs
            $validator = new Validator($_POST);
            $validator->required('nome')
                      ->required('email')
                      ->required('senha')
                      ->required('perfil')
                      ->email('email')
                      ->min('nome', 3)
                      ->min('senha', 8)
                      ->password('senha')
                      ->in('perfil', [
                          Constants::PERFIL_ADMIN,
                          Constants::PERFIL_MEDICO,
                          Constants::PERFIL_ENFERMEIRO,
                          Constants::PERFIL_TECNICO_ENFERMAGEM,
                          Constants::PERFIL_PACIENTE
                      ]);

            if ($validator->fails()) {
                Session::flash('error', $validator->getFirstError());
                require_once __DIR__ . '/../views/usuarios/cadastrar.php';
                return;
            }

            $data = $validator->getData();

            try {
                $resultado = $this->usuarioModel->criar($data);

                if ($resultado) {
                    // Assign the corresponding RBAC role.
                    RBAC::atribuirRole(
                        (int) ($this->usuarioModel->getByEmail($data['email'])['id'] ?? 0),
                        $data['perfil']
                    );

                    Logger::audit(Constants::AUDITORIA_ACAO_CRIAR_USUARIO, [
                        'nome' => $data['nome'],
                        'email' => $data['email'],
                        'perfil' => $data['perfil']
                    ]);

                    (new Auditoria())->registrar(
                        Constants::AUDITORIA_ACAO_CRIAR_USUARIO,
                        "usuario criado: {$data['nome']}",
                        ['email' => $data['email'], 'perfil' => $data['perfil']]
                    );
                    
                    Session::flash('success', 'Usuário cadastrado com sucesso');
                    header('Location: ' . BASE_URL . '/usuario/index');
                    exit;
                } else {
                    Session::flash('error', 'Erro ao cadastrar usuário');
                    require_once __DIR__ . '/../views/usuarios/cadastrar.php';
                }
            } catch (DatabaseException $e) {
                Logger::error("Erro ao cadastrar usuário", ['error' => $e->getMessage()]);
                Session::flash('error', $e->getMessage());
                require_once __DIR__ . '/../views/usuarios/cadastrar.php';
            }
        }
        require_once __DIR__ . '/../views/usuarios/cadastrar.php';
    }

    public function cadastrar_profissional() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF
            if (!CSRF::validateRequest()) {
                Session::flash('error', 'Token de segurança inválido');
                require_once __DIR__ . '/../views/usuarios/cadastrar_profissional.php';
                return;
            }

            // Validate inputs
            $validator = new Validator($_POST);
            $validator->required('id')
                      ->required('especialidade')
                      ->required('numero_registro')
                      ->integer('id')
                      ->min('especialidade', 3);

            if ($validator->fails()) {
                Session::flash('error', $validator->getFirstError());
                require_once __DIR__ . '/../views/usuarios/cadastrar_profissional.php';
                return;
            }

            $data = $validator->getData();

            // Business rule: professional records are only created for health
            // professional accounts (medico/enfermeiro/tecnico_enfermagem).
            $alvo = $this->usuarioModel->find((int) $data['id']);
            if (!$alvo || !in_array($alvo['perfil'], [
                Constants::PERFIL_MEDICO,
                Constants::PERFIL_ENFERMEIRO,
                Constants::PERFIL_TECNICO_ENFERMAGEM
            ], true)) {
                Session::flash('error', 'O usuário selecionado não é um profissional de saúde habilitado.');
                require_once __DIR__ . '/../views/usuarios/cadastrar_profissional.php';
                return;
            }

            try {
                $foto = '';
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $arquivo = $_FILES['foto'];
                    $extensaoBase = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

                    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
                        Session::flash('error', 'Erro no envio da foto. Tente novamente.');
                        require_once __DIR__ . '/../views/usuarios/cadastrar_profissional.php';
                        return;
                    }
                    if (!in_array($extensaoBase, $extensoesPermitidas, true)) {
                        Session::flash('error', 'Formato de imagem invalido. Use JPG, PNG ou WEBP.');
                        require_once __DIR__ . '/../views/usuarios/cadastrar_profissional.php';
                        return;
                    }
                    if ($arquivo['size'] > 2 * 1024 * 1024) {
                        Session::flash('error', 'A foto deve ter no maximo 2MB.');
                        require_once __DIR__ . '/../views/usuarios/cadastrar_profissional.php';
                        return;
                    }

                    $extensao = $extensaoBase === 'jpeg' ? 'jpg' : $extensaoBase;
                    $nomeArquivo = 'prof_' . (int) $data['id'] . '_' . time() . '.' . $extensao;
                    $destinoAbsoluto = dirname(__DIR__, 2) . '/public/Assets/images/usuario/' . $nomeArquivo;

                    if (!is_dir(dirname($destinoAbsoluto))) {
                        mkdir(dirname($destinoAbsoluto), 0777, true);
                    }
                    if (!move_uploaded_file($arquivo['tmp_name'], $destinoAbsoluto)) {
                        Session::flash('error', 'Nao foi possivel salvar a foto do profissional.');
                        require_once __DIR__ . '/../views/usuarios/cadastrar_profissional.php';
                        return;
                    }

                    $foto = 'Assets/images/usuario/' . $nomeArquivo;
                }

                $resultado = $this->profissionalModel->criar([
                    'fk_usuario' => $data['id'],
                    'especialidade' => $data['especialidade'],
                    'numero_registro' => $data['numero_registro'],
                    'foto' => $foto
                ]);

                if ($resultado) {
                    Logger::audit(Constants::AUDITORIA_ACAO_CRIAR_USUARIO, [
                        'tipo' => 'profissional',
                        'fk_usuario' => $data['id'],
                        'especialidade' => $data['especialidade']
                    ]);
                    
                    Session::flash('success', 'Profissional cadastrado com sucesso');
                    header('Location: ' . BASE_URL . '/usuario/index');
                    exit;
                } else {
                    Session::flash('error', 'Erro ao cadastrar profissional');
                    require_once __DIR__ . '/../views/usuarios/cadastrar_profissional.php';
                }
            } catch (DatabaseException $e) {
                Logger::error("Erro ao cadastrar profissional", ['error' => $e->getMessage()]);
                Session::flash('error', 'Erro ao cadastrar profissional');
                require_once __DIR__ . '/../views/usuarios/cadastrar_profissional.php';
            }
        }
        require_once __DIR__ . '/../views/usuarios/cadastrar_profissional.php';
    }

    public function cadastrar_paciente() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF
            if (!CSRF::validateRequest()) {
                Session::flash('error', 'Token de segurança inválido');
                require_once __DIR__ . '/../views/usuarios/cadastrar_paciente.php';
                return;
            }

            // Validate inputs
            $validator = new Validator($_POST);
            $validator->required('id')
                      ->integer('id')
                      ->required('cpf')
                      ->cpf('cpf')
                      ->required('telefone_principal')
                      ->required('data_nascimento')
                      ->dateNascimento('data_nascimento')
                      ->date('convenio_validade');

            if ($validator->fails()) {
                Session::flash('error', $validator->getFirstError());
                require_once __DIR__ . '/../views/usuarios/cadastrar_paciente.php';
                return;
            }

            $data = $validator->getData();

            // Business rule: a patient profile can only be completed for
            // accounts registered as "paciente".
            $alvo = $this->usuarioModel->find((int) $data['id']);
            if (!$alvo || $alvo['perfil'] !== Constants::PERFIL_PACIENTE) {
                Session::flash('error', 'O usuário selecionado não é um perfil de paciente habilitado.');
                require_once __DIR__ . '/../views/usuarios/cadastrar_paciente.php';
                return;
            }

            $foto = '';
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $arquivo = $_FILES['foto'];
                $extensaoBase = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
                $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

                if ($arquivo['error'] !== UPLOAD_ERR_OK) {
                    Session::flash('error', 'Erro no envio da foto. Tente novamente.');
                    require_once __DIR__ . '/../views/usuarios/cadastrar_paciente.php';
                    return;
                }
                if (!in_array($extensaoBase, $extensoesPermitidas, true)) {
                    Session::flash('error', 'Formato de imagem invalido. Use JPG, PNG ou WEBP.');
                    require_once __DIR__ . '/../views/usuarios/cadastrar_paciente.php';
                    return;
                }
                if ($arquivo['size'] > 2 * 1024 * 1024) {
                    Session::flash('error', 'A foto deve ter no maximo 2MB.');
                    require_once __DIR__ . '/../views/usuarios/cadastrar_paciente.php';
                    return;
                }

                $extensao = $extensaoBase === 'jpeg' ? 'jpg' : $extensaoBase;
                $nomeArquivo = 'pac_' . (int) $data['id'] . '_' . time() . '.' . $extensao;
                $destinoAbsoluto = dirname(__DIR__, 2) . '/public/Assets/images/usuario/' . $nomeArquivo;

                if (!is_dir(dirname($destinoAbsoluto))) {
                    mkdir(dirname($destinoAbsoluto), 0777, true);
                }

                if (!move_uploaded_file($arquivo['tmp_name'], $destinoAbsoluto)) {
                    Session::flash('error', 'Nao foi possivel salvar a foto do paciente.');
                    require_once __DIR__ . '/../views/usuarios/cadastrar_paciente.php';
                    return;
                }

                $foto = 'Assets/images/usuario/' . $nomeArquivo;
            }

            try {
                $resultado = $this->pacienteModel->criar([
                    'fk_usuario'          => $data['id'],
                    'cpf'                 => preg_replace('/\D/', '', $data['cpf'] ?? ''),
                    'rg'                  => $data['rg'] ?? '',
                    'telefone_principal'  => preg_replace('/\D/', '', $data['telefone_principal'] ?? ''),
                    'sexo'                => $data['sexo'] ?? '',
                    'estado_civil'        => $data['estado_civil'] ?? '',
                    'endereco_rua'        => $data['endereco_rua'] ?? '',
                    'endereco_numero'     => $data['endereco_numero'] ?? '',
                    'endereco_complemento' => $data['endereco_complemento'] ?? '',
                    'endereco_bairro'     => $data['endereco_bairro'] ?? '',
                    'endereco_cidade'     => $data['endereco_cidade'] ?? '',
                    'endereco_uf'         => $data['endereco_uf'] ?? '',
                    'endereco_cep'        => preg_replace('/\D/', '', $data['endereco_cep'] ?? ''),
                    'contato_nome'        => $data['contato_nome'] ?? '',
                    'contato_parentesco'  => $data['contato_parentesco'] ?? '',
                    'contato_telefone'    => preg_replace('/\D/', '', $data['contato_telefone'] ?? ''),
                    'convenio_operadora'  => $data['convenio_operadora'] ?? '',
                    'convenio_carteirinha'=> $data['convenio_carteirinha'] ?? '',
                    'convenio_validade'   => $data['convenio_validade'] ?? null,
                    'foto'                => $foto,
                    'data_nascimento'     => $data['data_nascimento'],
                    'contato_emergencia'  => preg_replace('/\D/', '', $data['tel_emergencia'] ?? ''),
                    'alergias'            => $data['alergias'] ?? '',
                    'doencas_previas'     => $data['doencas_previas'] ?? '',
                    'medicamentos_uso'    => $data['medicamentos_uso'] ?? '',
                    'cirurgias'           => $data['cirurgias'] ?? ''
                ]);

                if ($resultado) {
                    Logger::audit(Constants::AUDITORIA_ACAO_CRIAR_USUARIO, [
                        'tipo' => 'paciente',
                        'fk_usuario' => $data['id']
                    ]);

                    (new Auditoria())->registrar(
                        Constants::AUDITORIA_ACAO_CRIAR_USUARIO,
                        'paciente cadastrado',
                        ['fk_usuario' => $data['id']]
                    );

                    Session::flash('success', 'Paciente cadastrado com sucesso');
                    header('Location: ' . BASE_URL . '/usuario/index');
                    exit;
                } else {
                    Session::flash('error', 'Erro ao cadastrar paciente');
                    require_once __DIR__ . '/../views/usuarios/cadastrar_paciente.php';
                }
            } catch (DatabaseException $e) {
                Logger::error("Erro ao cadastrar paciente", ['error' => $e->getMessage()]);
                Session::flash('error', 'Erro ao cadastrar paciente');
                require_once __DIR__ . '/../views/usuarios/cadastrar_paciente.php';
            }
        }
        require_once __DIR__ . '/../views/usuarios/cadastrar_paciente.php';
    }

    public function editar($id = null) {
        $id = $id ?? ($_GET['id'] ?? null);
        if (!$id) die("ID não fornecido.");

        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) die("Usuário não encontrado.");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF
            if (!CSRF::validateRequest()) {
                Session::flash('error', 'Token de segurança inválido');
                require_once __DIR__ . '/../views/usuarios/editar.php';
                return;
            }

            // Validate inputs
            $validator = new Validator($_POST);
            $validator->required('nome')
                      ->required('email')
                      ->required('perfil')
                      ->email('email')
                      ->min('nome', 3)
                      ->in('perfil', [
                          Constants::PERFIL_ADMIN,
                          Constants::PERFIL_MEDICO,
                          Constants::PERFIL_ENFERMEIRO,
                          Constants::PERFIL_TECNICO_ENFERMAGEM,
                          Constants::PERFIL_PACIENTE
                      ]);

            $novaSenha = $_POST['senha'] ?? '';
            if ($novaSenha !== '') {
                $validator->min('senha', 8)
                          ->password('senha')
                          ->required('confirmar_senha')
                          ->matches('confirmar_senha', 'senha');
            }

            if ($validator->fails()) {
                Session::flash('error', $validator->getFirstError());
                require_once __DIR__ . '/../views/usuarios/editar.php';
                return;
            }

            $dados = $validator->getData();

            // Protection: an admin may not demote their own account.
            if ((int) $id === (int) Session::get('usuario_id')
                && (string) $dados['perfil'] !== (string) $usuario['perfil']) {
                Session::flash('error', 'Você não pode alterar o próprio perfil de acesso.');
                require_once __DIR__ . '/../views/usuarios/editar.php';
                return;
            }

            // Protection: the last active admin cannot be demoted/deactivated.
            if ($usuario['perfil'] === Constants::PERFIL_ADMIN
                && $dados['perfil'] !== Constants::PERFIL_ADMIN
                && $this->usuarioModel->contarAdminsAtivos() <= 1) {
                Session::flash('error', 'Não é possível remover o último administrador do sistema.');
                require_once __DIR__ . '/../views/usuarios/editar.php';
                return;
            }

            try {
                $this->usuarioModel->atualizar($id, [
                    'nome'   => $dados['nome'],
                    'email'  => $dados['email'],
                    'perfil' => $dados['perfil']
                ]);

                if ($novaSenha !== '') {
                    $this->usuarioModel->trocarSenha((int) $id, $novaSenha);
                }

                // Centralize RBAC: the role now mirrors the edited profile.
                RBAC::redefinirRole((int) $id, $dados['perfil']);
                
                Logger::audit(Constants::AUDITORIA_ACAO_ATUALIZAR_USUARIO, [
                    'id' => $id,
                    'nome' => $dados['nome']
                ]);

                (new Auditoria())->registrar(
                    Constants::AUDITORIA_ACAO_ATUALIZAR_USUARIO,
                    "usuario atualizado: {$dados['nome']}",
                    ['id' => $id]
                );
                
                Session::flash('success', 'Usuário atualizado com sucesso');
                header('Location: ' . BASE_URL . '/usuario/index');
                exit;
            } catch (DatabaseException $e) {
                Logger::error("Erro ao atualizar usuário", ['error' => $e->getMessage()]);
                Session::flash('error', 'Erro ao atualizar usuário');
                require_once __DIR__ . '/../views/usuarios/editar.php';
            }
        }

        require_once __DIR__ . '/../views/usuarios/editar.php';
    }

    public function excluir($id = null){
        // Business rule: deactivating a user requires a valid POST with CSRF
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Requisição inválida (token de segurança)');
            header('Location: ' . BASE_URL . '/usuario/index');
            exit;
        }

        $id = $id ?? ($_POST['id'] ?? null);
        if ($id) {
            if ((int) $id === (int) Session::get('usuario_id')) {
                Session::flash('error', 'Você não pode desativar o seu próprio usuário.');
                header('Location: ' . BASE_URL . '/usuario/index');
                exit;
            }

            $alvo = $this->usuarioModel->find((int) $id);
            try {
                if ($alvo && $alvo['perfil'] === Constants::PERFIL_ADMIN
                    && $this->usuarioModel->contarAdminsAtivos() <= 1) {
                    Session::flash('error', 'Não é possível desativar o último administrador do sistema.');
                    header('Location: ' . BASE_URL . '/usuario/index');
                    exit;
                }

                $this->usuarioModel->desativar((int) $id);

                // Remove RBAC roles from the deactivated user.
                RBAC::removerRoles((int) $id);
                
                Logger::audit(Constants::AUDITORIA_ACAO_DESATIVAR_USUARIO, [
                    'id' => $id
                ]);

                (new Auditoria())->registrar(
                    Constants::AUDITORIA_ACAO_DESATIVAR_USUARIO,
                    'usuario desativado',
                    ['id' => $id]
                );
                
                Session::flash('success', 'Usuário desativado com sucesso');
            } catch (DatabaseException $e) {
                Logger::error("Erro ao desativar usuário", ['error' => $e->getMessage()]);
                Session::flash('error', 'Erro ao desativar usuário');
            }
        }

        header('Location: ' . BASE_URL . '/usuario/index');
        exit;
    }

    /**
     * Reactivates a deactivated user and restores their RBAC role.
     */
    public function reativar($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Requisição inválida (token de segurança)');
            header('Location: ' . BASE_URL . '/usuario/index');
            exit;
        }

        $id = $id ? (int) $id : (int) ($_POST['id'] ?? 0);
        if (!$id) {
            Session::flash('error', 'Usuário inválido.');
            header('Location: ' . BASE_URL . '/usuario/index');
            exit;
        }

        try {
            $this->usuarioModel->reativar($id);
            $role = $this->usuarioModel->find($id); // find() only returns active rows
            RBAC::atribuirRole($id, $role ? $role['perfil'] : Constants::PERFIL_PACIENTE);

            (new Auditoria())->registrar(
                Constants::AUDITORIA_ACAO_ATUALIZAR_USUARIO,
                'usuario reativado',
                ['id' => $id]
            );

            Session::flash('success', 'Usuário reativado com sucesso');
        } catch (DatabaseException $e) {
            Logger::error("Erro ao reativar usuário", ['error' => $e->getMessage()]);
            Session::flash('error', 'Erro ao reativar usuário');
        }

        header('Location: ' . BASE_URL . '/usuario/index');
        exit;
    }
        public function procurar() {
        $termo = $_GET['nome'] ?? '';
        if(empty($termo)){
            require dirname(__DIR__, 2) . '/app/views/usuarios/cadastrar_profissional.php';
        } else {
            $usuarios = $this->usuarioModel->buscarPorNomeProfissional($termo);
            require dirname(__DIR__, 2) . '/app/views/usuarios/cadastrar_profissional.php';
        }
    }

    public function procurar_paciente() {
        $termo = $_GET['nome'] ?? '';
        $todosOsUsuarios = $this->usuarioModel->buscarPorNomeOuCpfPaciente('');
        if(empty($termo)){
            require dirname(__DIR__, 2) . '/app/views/usuarios/cadastrar_paciente.php';
        } else {
            $usuarios = $this->usuarioModel->buscarPorNomeOuCpfPaciente($termo);
            require dirname(__DIR__, 2) . '/app/views/usuarios/cadastrar_paciente.php';
        }
    }

    public function listar() {
        $filtros = [
            'id'     => trim($_GET['id'] ?? ''),
            'nome'   => trim($_GET['nome'] ?? ''),
            'email'  => trim($_GET['email'] ?? ''),
            'perfil' => trim($_GET['perfil'] ?? ''),
        ];
        $usuarios = $this->usuarioModel->buscarUsuarios($filtros);
        require dirname(__DIR__, 2) . '/app/views/usuarios/index.php';
    }

    public function listarProfissionaisAtendendo() {
        $id_paciente = Session::get('usuario_id');
        $profissionais = $this->profissionalModel->listarAtivos();
        require dirname(__DIR__, 2) . '/app/views/paciente/solicitar_agendamento.php';
    }
}
