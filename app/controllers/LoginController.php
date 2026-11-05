<?php
class LoginController {
    private $model;

    public function __construct() {
        Session::start();
        $this->model = new Usuario();
    }

    public function index() {
        if (Session::has('usuario_id')) {
            $this->destinoAposLogin();
        }
        $modo = 'login';
        require_once __DIR__ . '/../views/auth/index.php';
    }

    /**
     * Sends a logged user to their dashboard (or to the profile chooser).
     */
    private function destinoAposLogin() {
        $perfil = Session::get('usuario_perfil');
        if ($perfil) {
            $rotas = [
                Constants::PERFIL_ADMIN => 'painel',
                Constants::PERFIL_MEDICO => 'medico',
                Constants::PERFIL_ENFERMEIRO => 'enfermeiro',
                Constants::PERFIL_TECNICO_ENFERMAGEM => 'tecnicoenfermagem',
                Constants::PERFIL_PACIENTE => 'paciente'
            ];
            header('Location: ' . BASE_URL . '/' . ($rotas[$perfil] ?? 'paciente') . '/index');
        } else {
            header('Location: ' . BASE_URL . '/login/escolher_perfil');
        }
        exit;
    }

    public function logar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        // Validate CSRF
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Token de segurança inválido');
            $modo = 'login';
            require_once __DIR__ . '/../views/auth/index.php';
            return;
        }

        // Brute-force protection: block after too many failed attempts.
        if ($this->bloqueadoPorTentativas()) {
            $modo = 'login';
            require_once __DIR__ . '/../views/auth/index.php';
            return;
        }

        // Validate inputs (the profile is chosen AFTER login)
        $validator = new Validator($_POST);
        $validator->required('email')
                  ->required('senha')
                  ->email('email');

        if ($validator->fails()) {
            $this->registrarFalhaLogin();
            Session::flash('error', $validator->getFirstError());
            $modo = 'login';
            require_once __DIR__ . '/../views/auth/index.php';
            return;
        }

        $data = $validator->getData();
        $email = trim($data['email']);
        $senha = $data['senha'];

        $usuario = $this->model->verificarLogin($email, $senha);

        if ($usuario) {
            // Successful login clears the brute-force counters.
            Session::remove('login_tentativas');
            Session::remove('login_bloqueado_ate');

            // Regenerate the session ID to prevent session fixation
            session_regenerate_id(true);

            Session::set('usuario_id', $usuario['id']);
            Session::set('usuario_nome', $usuario['nome']);

            // Audit log
            Logger::audit(Constants::AUDITORIA_ACAO_LOGIN, [
                'email'  => $email,
                'perfil' => $usuario['perfil']
            ]);

            (new Auditoria())->registrar(
                Constants::AUDITORIA_ACAO_LOGIN,
                "{$usuario['nome']} fez login",
                ['perfil' => $usuario['perfil']]
            );

            // First access: the user must define a new password before using the system.
            if (!empty($usuario['primeiro_acesso'])) {
                Session::set('usuario_perfil', $usuario['perfil'] ?? '');
                Session::set('forcar_troca_senha', true);
                RBAC::carregarSessao((int) $usuario['id']);
                Session::flash('error', 'Antes de continuar, defina uma nova senha para sua conta.');
                header('Location: ' . BASE_URL . '/perfil/trocar_senha');
                exit;
            }

            // Admin profile is hidden and enters automatically with its
            // own credentials (admin@gmail.com / Admin@123).
            if ($usuario['perfil'] === Constants::PERFIL_ADMIN) {
                Session::set('usuario_perfil', Constants::PERFIL_ADMIN);
                RBAC::carregarSessao((int) $usuario['id']);
                header('Location: ' . BASE_URL . '/painel/index');
                exit;
            }

            // Standard user: enters the system and then chooses the
            // profile/role to use in this session.
            Session::set('usuario_perfil', $usuario['perfil'] ?? '');
            header('Location: ' . BASE_URL . '/login/escolher_perfil');
            exit;
        } else {
            $this->registrarFalhaLogin();
            Session::flash('error', 'Dados de acesso inválidos');
            $modo = 'login';
            require_once __DIR__ . '/../views/auth/index.php';
        }
    }

    /**
     * Checks whether the session is currently blocked by failed login attempts.
     */
    private function bloqueadoPorTentativas(): bool {
        $bloqueadoAte = (int) Session::get('login_bloqueado_ate', 0);
        if (time() < $bloqueadoAte) {
            $restante = max(1, (int) ceil(($bloqueadoAte - time()) / 60));
            Session::flash('error', "Muitas tentativas de login. Tente novamente em {$restante} minuto(s).");
            return true;
        }
        if ($bloqueadoAte > 0) {
            Session::remove('login_tentativas');
            Session::remove('login_bloqueado_ate');
        }
        return false;
    }

    /**
     * Counts a failed login attempt and sets the block when the limit is reached.
     */
    private function registrarFalhaLogin(): void {
        $tentativas = (int) Session::get('login_tentativas', 0) + 1;
        Session::set('login_tentativas', $tentativas);
        if ($tentativas >= Constants::LOGIN_MAX_TENTATIVAS) {
            Session::set('login_bloqueado_ate', time() + Constants::LOGIN_BLOQUEIO_MINUTOS * 60);
        }
    }

    /**
     * Profile chooser after the basic login.
     * Lists only the public profiles (admin is never shown here).
     */
    public function escolher_perfil() {
        $usuarioId = (int) Session::get('usuario_id');
        if (!$usuarioId) {
            Session::flash('error', 'Sessão expirada. Faça login novamente.');
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::validateRequest()) {
                Session::flash('error', 'Token de segurança inválido');
                $this->apresentarEscolhaPerfil($usuarioId);
                return;
            }

            $perfil = trim($_POST['perfil'] ?? '');

            // Whitelist first: admin is not an option for anyone.
            if (!in_array($perfil, Constants::PERFIS_AUTOCADASTRO, true)) {
                Session::flash('error', 'Perfil de acesso inválido. Escolha uma das opções disponíveis.');
                $this->apresentarEscolhaPerfil($usuarioId, $perfil);
                return;
            }

            // The user must actually hold the chosen role.
            $roles = array_column(RBAC::rolesDoUsuario($usuarioId), 'slug');
            if (!in_array($perfil, $roles, true)) {
                Session::flash('error', 'Você não possui esse perfil de acesso.');
                $this->apresentarEscolhaPerfil($usuarioId, $perfil);
                return;
            }

            Session::set('usuario_perfil', $perfil);
            RBAC::carregarSessao($usuarioId);

            Logger::audit('escolher_perfil', [
                'usuario_id' => $usuarioId,
                'perfil'     => $perfil
            ]);

            $rotas = [
                Constants::PERFIL_MEDICO => 'medico',
                Constants::PERFIL_ENFERMEIRO => 'enfermeiro',
                Constants::PERFIL_TECNICO_ENFERMAGEM => 'tecnicoenfermagem',
                Constants::PERFIL_PACIENTE => 'paciente'
            ];
            header('Location: ' . BASE_URL . '/' . ($rotas[$perfil] ?? 'paciente') . '/index');
            exit;
        }

        $this->apresentarEscolhaPerfil($usuarioId);
    }

    private function apresentarEscolhaPerfil(int $usuarioId, string $selecionado = '') {
        $perfisDisponiveis = array_values(array_filter(
            RBAC::rolesDoUsuario($usuarioId),
            fn($role) => in_array($role['slug'], Constants::PERFIS_AUTOCADASTRO, true)
        ));

        // Fallback: the profile stored on the account.
        if (empty($perfisDisponiveis)) {
            $perfilConta = Session::get('usuario_perfil');
            if (in_array($perfilConta, Constants::PERFIS_AUTOCADASTRO, true)) {
                $perfisDisponiveis = [['slug' => $perfilConta, 'nome' => ucwords(str_replace('_', ' ', $perfilConta))]];
            }
        }

        require_once __DIR__ . '/../views/auth/escolher_perfil.php';
    }

    public function sair() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        // Audit log
        Logger::audit(Constants::AUDITORIA_ACAO_LOGOUT, [
            'user_id' => Session::get('usuario_id'),
            'user_nome' => Session::get('usuario_nome')
        ]);

        (new Auditoria())->registrar(
            Constants::AUDITORIA_ACAO_LOGOUT,
            "usuario saiu do sistema",
            ['usuario_id' => Session::get('usuario_id')]
        );

        Session::destroy();
        header('Location: ' . BASE_URL . '/login/index');
        exit;
    }

    // ------------------------------------------------------------------
    // Password recovery
    // ------------------------------------------------------------------

    public function recuperar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::validateRequest()) {
                Session::flash('error', 'Token de segurança inválido');
                $this->renderRecuperar();
                return;
            }

            $validator = new Validator($_POST);
            $validator->required('email')->email('email');

            if ($validator->fails()) {
                Session::flash('error', $validator->getFirstError());
                $this->renderRecuperar();
                return;
            }

            $email = $validator->getData()['email'];
            $usuario = $this->model->getByEmail($email);

            if ($usuario) {
                $token = (new RecuperacaoSenha())->criar($usuario['id']);
                if ($token) {
                    $link = BASE_URL . '/login/redefinir/' . $token;
                    Logger::info('Recuperação de senha solicitada', [
                        'usuario_id' => $usuario['id'],
                        'email'      => $email,
                        'link'       => $link
                    ]);

                    $enviado = $this->enviarEmailRecuperacao($email, $link);

                    if ($enviado) {
                        Session::flash('success', 'Enviamos um link de redefinição de senha para o seu e-mail. Verifique sua caixa de entrada.');
                    } elseif (Env::get('APP_ENV', 'production') === 'development') {
                        // No SMTP configured: expose the link only in development (demo).
                        Session::flash('success', "Link de redefinição (ambiente de desenvolvimento): {$link}");
                    } else {
                        Session::flash('success', 'Se o e-mail estiver cadastrado, você receberá um link de redefinição de senha.');
                    }
                }
            } else {
                Session::flash('success', 'Se o e-mail estiver cadastrado, você receberá um link de redefinição de senha.');
            }

            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        $this->renderRecuperar();
    }

    public function redefinir($token = null) {
        $token = trim((string) $token);
        $rec = new RecuperacaoSenha();
        $row = $rec->buscarValido($token);

        if (!$row) {
            Session::flash('error', 'Link inválido ou expirado. Solicite uma nova recuperação de senha.');
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRF::validateRequest()) {
                Session::flash('error', 'Token de segurança inválido');
                $this->renderRedefinir($token);
                return;
            }

            $validator = new Validator($_POST);
            $validator->required('senha')
                      ->min('senha', 8)
                      ->password('senha')
                      ->required('confirmar_senha')
                      ->matches('confirmar_senha', 'senha');

            if ($validator->fails()) {
                Session::flash('error', $validator->getFirstError());
                $this->renderRedefinir($token);
                return;
            }

            $novaSenha = $validator->getData()['senha'];

            if ($this->model->trocarSenha($row['fk_usuario'], $novaSenha)) {
                $rec->marcarUsado($token);
                Logger::audit(Constants::AUDITORIA_ACAO_REDEFINIR_SENHA, ['usuario_id' => $row['fk_usuario']]);
                (new Auditoria())->registrar(
                    Constants::AUDITORIA_ACAO_REDEFINIR_SENHA,
                    "{$row['nome']} redefiniu a senha pelo link de recuperação",
                    ['usuario_id' => $row['fk_usuario']]
                );
                Session::flash('success', 'Senha redefinida com sucesso. Faça login com a nova senha.');
                header('Location: ' . BASE_URL . '/login/index');
                exit;
            }

            Session::flash('error', 'Não foi possível redefinir a senha. Tente novamente.');
            $this->renderRedefinir($token);
            return;
        }

        $this->renderRedefinir($token);
    }

    private function renderRecuperar() {
        $email = $_POST['email'] ?? '';
        require_once __DIR__ . '/../views/auth/recuperar.php';
    }

    /**
     * Sends the recovery e-mail through the SMTP configured in .env.
     */
    private function enviarEmailRecuperacao(string $email, string $link): bool {
        try {
            return Mailer::enviar(
                $email,
                'Recuperação de senha - EloMed',
                '<p>Olá,</p>' .
                '<p>Recebemos uma solicitação para redefinir a senha da sua conta no EloMed.</p>' .
                '<p>Para criar uma nova senha, acesse o link abaixo (válido por ' . Constants::RECUPERACAO_EXPIRACAO_MINUTOS . ' minutos):</p>' .
                '<p><a href="' . e($link) . '">' . e($link) . '</a></p>' .
                '<p>Se você não solicitou, ignore este e-mail. Sua senha atual continua valendo.</p>',
                "Redefina sua senha acessando: {$link} (válido por " . Constants::RECUPERACAO_EXPIRACAO_MINUTOS . ' minutos).'
            );
        } catch (\Throwable $e) {
            Logger::warning('Falha ao preparar e-mail de recuperação', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function renderRedefinir(string $token) {
        require_once __DIR__ . '/../views/auth/redefinir.php';
    }

    // ------------------------------------------------------------------
    // Google OAuth sign-in ("Entrar com Google")
    // ------------------------------------------------------------------

    public function google() {
        $clientId = Env::get('GOOGLE_CLIENT_ID');
        $clientSecret = Env::get('GOOGLE_CLIENT_SECRET');

        if (empty($clientId) || empty($clientSecret)) {
            Session::flash('error', 'Login com Google ainda não foi configurado nesta instalação.');
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        $redirect = Env::get('GOOGLE_REDIRECT_URI', BASE_URL . '/login/google_callback');
        $state = bin2hex(random_bytes(16));
        Session::set('google_oauth_state', $state);

        $params = [
            'client_id'     => $clientId,
            'redirect_uri'  => $redirect,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'prompt'        => 'select_account',
            'state'         => $state
        ];

        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
        exit;
    }

    public function google_callback() {
        $clientId = Env::get('GOOGLE_CLIENT_ID');
        $clientSecret = Env::get('GOOGLE_CLIENT_SECRET');
        $redirect = Env::get('GOOGLE_REDIRECT_URI', BASE_URL . '/login/google_callback');

        $estadoSessao = Session::get('google_oauth_state');
        Session::remove('google_oauth_state');

        if (empty($estadoSessao) || !isset($_GET['state']) || !hash_equals($estadoSessao, $_GET['state'])) {
            Logger::warning('Callback do Google: estado inválido', [
                'sessao_tem_estado' => !empty($estadoSessao),
                'get_tem_state'     => isset($_GET['state']),
                'ip'                => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            Session::flash('error', 'Falha na autenticação com Google (estado inválido). Tente novamente.');
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        if (isset($_GET['error']) || empty($_GET['code'])) {
            Logger::warning('Callback do Google: erro ou code ausente', ['erro' => $_GET['error'] ?? null]);
            Session::flash('error', 'Autenticação com Google cancelada ou falhou. Tente novamente.');
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        $accessToken = $this->obterTokenGoogle($_GET['code'], $clientId, $clientSecret, $redirect);
        if (!$accessToken) {
            Logger::warning('Callback do Google: falha ao obter token de acesso', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            Session::flash('error', 'Falha ao obter credenciais do Google. Tente novamente.');
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        $info = $this->obterInfoGoogle($accessToken);
        if (empty($info['email'])) {
            Logger::warning('Callback do Google: userinfo sem e-mail', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
            Session::flash('error', 'Não foi possível obter seu e-mail do Google. Tente novamente.');
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        $email = strtolower(trim($info['email']));
        $nome = trim($info['name'] ?? $this->nomePelaParteDoEmail($email));
        $usuario = $this->model->getByEmail($email);

        if ($usuario) {
            if ($usuario['status'] !== 'ativo') {
                Logger::warning('Callback do Google: conta desativada', ['email' => $email]);
                Session::flash('error', 'Conta desativada. Contate o administrador.');
                header('Location: ' . BASE_URL . '/login/index');
                exit;
            }
            // Google sign-in proves identity: user is no longer on first access.
            $this->model->limparPrimeiroAcesso($usuario['id']);
            $this->finalizarLoginGoogle($usuario);
            return;
        }

        // New account: ask which profile the user wants (never admin).
        Session::set('google_pendente', ['nome' => $nome, 'email' => $email]);
        Logger::info('Cadastro via Google iniciado (aguardando perfil)', [
            'email' => $email,
            'ip'    => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        $pendente = Session::get('google_pendente');
        $perfilEscolhido = '';
        require_once __DIR__ . '/../views/auth/google_perfil.php';
    }

    public function google_cadastrar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateRequest()) {
            Session::flash('error', 'Token de segurança inválido. Tente novamente.');
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        $pendente = Session::get('google_pendente');
        if (!$pendente || empty($pendente['email'])) {
            Session::flash('error', 'Sessão expirada ou inválida. Tente entrar com o Google novamente.');
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        $perfil = trim($_POST['perfil'] ?? '');
        if (!in_array($perfil, Constants::PERFIS_AUTOCADASTRO, true)) {
            Logger::warning('Cadastro via Google: perfil não permitido', ['perfil' => $perfil]);
            Session::flash('error', 'Perfil de acesso inválido. Escolha uma das opções disponíveis.');
            $perfilEscolhido = $perfil;
            require_once __DIR__ . '/../views/auth/google_perfil.php';
            return;
        }

        Session::remove('google_pendente');

        $nome = $pendente['nome'];
        $email = $pendente['email'];

        // The email may have registered meanwhile (double form submit).
        $usuario = $this->model->getByEmail($email);
        if ($usuario) {
            $this->finalizarLoginGoogle($usuario);
            return;
        }

        $novoId = $this->model->criarGoogle($nome, $email, $perfil);
        if (!$novoId) {
            Logger::warning('Cadastro via Google: falha ao criar usuário', ['email' => $email]);
            Session::flash('error', 'Não foi possível criar a conta. Contate o administrador.');
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        RBAC::atribuirRole($novoId, $perfil);
        Logger::audit(Constants::AUDITORIA_ACAO_CRIAR_USUARIO, [
            'via'    => 'google',
            'email'  => $email,
            'perfil' => $perfil
        ]);
        $this->finalizarLoginGoogle($this->model->getByEmail($email));
    }

    private function finalizarLoginGoogle(array $usuario) {
        session_regenerate_id(true);

        Session::set('usuario_id', $usuario['id']);
        Session::set('usuario_nome', $usuario['nome']);
        Session::set('usuario_perfil', $usuario['perfil']);
        Session::set('usuario_primeiro_acesso', false);

        RBAC::carregarSessao((int) $usuario['id']);

        Logger::audit(Constants::AUDITORIA_ACAO_LOGIN_GOOGLE, ['email' => $usuario['email']]);
        (new Auditoria())->registrar(
            Constants::AUDITORIA_ACAO_LOGIN,
            "{$usuario['nome']} entrou com Google",
            ['email' => $usuario['email']]
        );

        $rotas = [
            Constants::PERFIL_ADMIN => 'painel',
            Constants::PERFIL_MEDICO => 'medico',
            Constants::PERFIL_ENFERMEIRO => 'enfermeiro',
            Constants::PERFIL_TECNICO_ENFERMAGEM => 'tecnicoenfermagem',
            Constants::PERFIL_PACIENTE => 'paciente'
        ];

        header('Location: ' . BASE_URL . '/' . ($rotas[$usuario['perfil']] ?? 'paciente') . '/index');
        exit;
    }

    private function nomePelaParteDoEmail(string $email): string {
        $parte = explode('@', $email)[0] ?? 'Usuário';
        $provisorio = preg_replace('/[^a-zA-Z0-9._-]/', ' ', $parte);
        return ucwords(str_replace(['.', '_', '-'], ' ', $provisorio)) ?: 'Usuário';
    }

    private function obterTokenGoogle(string $code, string $clientId, string $clientSecret, string $redirect) {
        $resposta = $this->curlPost('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirect,
            'grant_type'    => 'authorization_code'
        ]);
        return $resposta['access_token'] ?? null;
    }

    private function obterInfoGoogle(string $accessToken) {
        $resposta = $this->curlGet('https://www.googleapis.com/oauth2/v3/userinfo', $accessToken);
        return $resposta;
    }

    private function curlPost(string $url, array $params) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 15
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode((string) $body, true);
        if ($code !== 200 || !is_array($decoded)) return null;
        return $decoded;
    }

    private function curlGet(string $url, string $accessToken) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 15
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode((string) $body, true);
        if ($code !== 200 || !is_array($decoded)) return null;
        return $decoded;
    }
}
