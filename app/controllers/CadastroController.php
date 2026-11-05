<?php
class CadastroController {
    private $model;

    public function __construct() {
        Session::start();
        $this->model = new Usuario();
    }

    public function index() {
        if (Session::has('usuario_id')) {
            $perfil = Session::get('usuario_perfil');
            if (!$perfil) {
                header('Location: ' . BASE_URL . '/login/escolher_perfil');
                exit;
            }
            $rotas = [
                Constants::PERFIL_ADMIN => 'painel',
                Constants::PERFIL_MEDICO => 'medico',
                Constants::PERFIL_ENFERMEIRO => 'enfermeiro',
                Constants::PERFIL_TECNICO_ENFERMAGEM => 'tecnicoenfermagem',
                Constants::PERFIL_PACIENTE => 'paciente'
            ];
            header('Location: ' . BASE_URL . '/' . ($rotas[$perfil] ?? 'paciente') . '/index');
            exit;
        }
        $modo = 'cadastro';
        require_once __DIR__ . '/../views/auth/index.php';
    }

    /**
     * Self-registration.
     *
     * NOTE: there is no public "check if this e-mail exists" endpoint on purpose.
     * Enumerating which addresses have an account would disclose registered
     * patients and staff. Uniqueness is enforced server-side instead: Usuario::criar()
     * raises a DatabaseException (MySQL 1062) that is surfaced as a flash message.
     */
    public function cadastrar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF
            if (!CSRF::validateRequest()) {
                Session::flash('error', 'Token de segurança inválido');
                $modo = 'cadastro';
                require_once __DIR__ . '/../views/auth/index.php';
                return;
            }

            // Validate inputs
            $validator = new Validator($_POST);
            $validator->required('nome')
                      ->required('email')
                      ->required('senha')
                      ->required('confirmar_senha')
                      ->required('perfil')
                      ->email('email')
                      ->min('nome', 3)
                      ->min('senha', 8)
                      ->password('senha')
                      ->matches('confirmar_senha', 'senha');

            if ($validator->fails()) {
                Session::flash('error', $validator->getFirstError());
                $modo = 'cadastro';
                require_once __DIR__ . '/../views/auth/index.php';
                return;
            }

            $data = $validator->getData();

            // Business rule: self-registration is restricted to allowed
            // profiles (medico/enfermeiro/tecnico_enfermagem/paciente).
            // Admin is never creatable through this flow.
            if (!in_array($data['perfil'], Constants::PERFIS_AUTOCADASTRO, true)) {
                Session::flash('error', 'Perfil de acesso inválido. Escolha uma das opções disponíveis.');
                $modo = 'cadastro';
                require_once __DIR__ . '/../views/auth/index.php';
                return;
            }

            try {
                $resultado = $this->model->criar($data);

                if ($resultado) {
                    $novoId = $this->model->getByEmail($data['email'])['id'] ?? null;

                    // Assign the RBAC role matching the chosen profile.
                    if ($novoId) {
                        RBAC::atribuirRole((int) $novoId, $data['perfil']);
                    }

                    session_regenerate_id(true);
                    Session::set('usuario_id', $novoId);
                    Session::set('usuario_nome', $data['nome']);
                    Session::set('usuario_perfil', $data['perfil']);
                    Session::set('usuario_primeiro_acesso', true);

                    // Load RBAC roles and permissions into the session.
                    if ($novoId) {
                        RBAC::carregarSessao((int) $novoId);
                    }
                    
                    // Audit log
                    Logger::audit(Constants::AUDITORIA_ACAO_CRIAR_USUARIO, [
                        'nome' => $data['nome'],
                        'email' => $data['email'],
                        'perfil' => $data['perfil']
                    ]);

                    $rotas = [
                        Constants::PERFIL_MEDICO => 'medico',
                        Constants::PERFIL_ENFERMEIRO => 'enfermeiro',
                        Constants::PERFIL_TECNICO_ENFERMAGEM => 'tecnicoenfermagem',
                        Constants::PERFIL_PACIENTE => 'paciente'
                    ];
                    $destino = $rotas[$data['perfil']] ?? 'paciente';

                    if ($data['perfil'] === Constants::PERFIL_PACIENTE) {
                        Session::flash('success', 'Cadastro realizado! Complete seus dados de paciente no menu "Completar Cadastro".');
                    } else {
                        Session::flash('success', 'Cadastro realizado com sucesso!');
                    }
                    header('Location: ' . BASE_URL . '/' . $destino . '/index');
                    exit;
                } else {
                    Session::flash('error', 'Erro ao cadastrar usuário');
                    $modo = 'cadastro';
                    require_once __DIR__ . '/../views/auth/index.php';
                }
            } catch (DatabaseException $e) {
                Logger::error("Erro ao cadastrar usuário", ['error' => $e->getMessage()]);
                Session::flash('error', $e->getMessage());
                $modo = 'cadastro';
                require_once __DIR__ . '/../views/auth/index.php';
            }
        }
        $modo = 'cadastro';
        require_once __DIR__ . '/../views/auth/index.php';
    }
}
