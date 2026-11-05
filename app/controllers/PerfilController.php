<?php
class PerfilController {
    private $usuarioModel;
    private $profissionalModel;

    public function __construct() {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }
        $this->usuarioModel = new Usuario();
        $this->profissionalModel = new Profissional();
    }

    private function perfisProfissionais() {
        return [
            Constants::PERFIL_MEDICO,
            Constants::PERFIL_ENFERMEIRO,
            Constants::PERFIL_TECNICO_ENFERMAGEM
        ];
    }

    /**
     * Stores an uploaded profile photo under Assets/images/usuario/.
     * Returns [relativePath, null] on success or [null, errorMessage] on failure.
     */
    private function salvarFoto(array $arquivo, string $prefixo, int $id): array {
        $extensaoBase = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            return [null, 'Erro no envio da foto. Tente novamente.'];
        }
        if (!in_array($extensaoBase, $permitidas, true)) {
            return [null, 'Formato de imagem inválido. Use JPG, PNG ou WEBP.'];
        }
        if ($arquivo['size'] > 2 * 1024 * 1024) {
            return [null, 'A foto deve ter no máximo 2MB.'];
        }

        $extensao = $extensaoBase === 'jpeg' ? 'jpg' : $extensaoBase;
        $nomeArquivo = $prefixo . '_' . $id . '_' . time() . '.' . $extensao;
        $destino = dirname(__DIR__, 2) . '/public/Assets/images/usuario/' . $nomeArquivo;

        if (!is_dir(dirname($destino))) {
            mkdir(dirname($destino), 0777, true);
        }
        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            return [null, 'Não foi possível salvar a foto.'];
        }

        return ['Assets/images/usuario/' . $nomeArquivo, null];
    }

    public function completar_cadastro() {
        $perfil = Session::get('usuario_perfil');

        // Patients already have their own profile-completion screen.
        if ($perfil === Constants::PERFIL_PACIENTE) {
            header('Location: ' . BASE_URL . '/paciente/completar_cadastro');
            exit;
        }

        $usuario = $this->usuarioModel->find((int) Session::get('usuario_id'));

        $profissional = null;
        if (in_array($perfil, $this->perfisProfissionais(), true)) {
            $profissional = $this->profissionalModel->getByUsuario((int) Session::get('usuario_id'));
        }

        require_once __DIR__ . '/../views/perfil/completar_cadastro.php';
    }

    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/perfil/completar_cadastro');
            exit;
        }

        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Token de segurança inválido');
            header('Location: ' . BASE_URL . '/perfil/completar_cadastro');
            exit;
        }

        $perfil = Session::get('usuario_perfil');

        // Patients have their own registration flow.
        if ($perfil === Constants::PERFIL_PACIENTE) {
            header('Location: ' . BASE_URL . '/paciente/completar_cadastro');
            exit;
        }

        $id = (int) Session::get('usuario_id');
        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            Session::flash('error', 'Usuário não encontrado.');
            header('Location: ' . BASE_URL . '/perfil/completar_cadastro');
            exit;
        }

        $ehProfissional = in_array($perfil, $this->perfisProfissionais(), true);

        $validator = new Validator($_POST);
        $validator->required('nome')
                  ->required('email')
                  ->email('email')
                  ->min('nome', 3);

        if ($ehProfissional) {
            $validator->required('especialidade')
                      ->min('especialidade', 3)
                      ->required('numero_registro');
        }

        $novaSenha = $_POST['senha'] ?? '';
        if ($novaSenha !== '') {
            $validator->required('senha_atual')->min('senha', 8)->password('senha');
        }

        if ($validator->fails()) {
            Session::flash('error', $validator->getFirstError());
            header('Location: ' . BASE_URL . '/perfil/completar_cadastro');
            exit;
        }

        $data = $validator->getData();

        if ($novaSenha !== '') {
            if (!password_verify($_POST['senha_atual'] ?? '', $usuario['senha'])) {
                Session::flash('error', 'A senha atual está incorreta.');
                header('Location: ' . BASE_URL . '/perfil/completar_cadastro');
                exit;
            }
        }

        try {
            $this->usuarioModel->atualizar($id, [
                'nome'   => $data['nome'],
                'email'  => $data['email'],
                'perfil' => $perfil
            ]);

            if ($novaSenha !== '') {
                $this->usuarioModel->trocarSenha($id, $novaSenha);
                Session::remove('forcar_troca_senha');
                Logger::audit(Constants::AUDITORIA_ACAO_REDEFINIR_SENHA, ['usuario_id' => $id]);
            }

            if ($ehProfissional) {
                $profissional = $this->profissionalModel->getByUsuario($id);

                $dadosProf = [
                    'especialidade'   => $data['especialidade'],
                    'numero_registro' => $data['numero_registro']
                ];

                // Optional profile photo (JPG/PNG/WEBP, max 2MB).
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                    [$novaFoto, $erroFoto] = $this->salvarFoto($_FILES['foto'], 'prof', $id);
                    if ($erroFoto !== null) {
                        Session::flash('error', $erroFoto);
                        header('Location: ' . BASE_URL . '/perfil/completar_cadastro');
                        exit;
                    }

                    // Remove the previous photo (if any) after the new one is saved.
                    if (!empty($profissional['foto'])) {
                        $antiga = dirname(__DIR__, 2) . '/public/' . $profissional['foto'];
                        if (is_file($antiga)) {
                            @unlink($antiga);
                        }
                    }

                    $dadosProf['foto'] = $novaFoto;
                }

                if ($profissional) {
                    $this->profissionalModel->atualizar($profissional['id'], $dadosProf);
                } else {
                    $this->profissionalModel->criar([
                        'fk_usuario'      => $id,
                        'especialidade'   => $data['especialidade'],
                        'numero_registro' => $data['numero_registro'],
                        'foto'            => $dadosProf['foto'] ?? null
                    ]);
                }
            }

            // Update the session if the name has changed.
            Session::set('usuario_nome', $data['nome']);

            (new Auditoria())->registrar(
                Constants::AUDITORIA_ACAO_ATUALIZAR_USUARIO,
                'cadastro complementado pelo proprio usuario',
                ['id' => $id, 'perfil' => $perfil]
            );

            Session::flash('success', 'Cadastro atualizado com sucesso');
        } catch (PDOException $e) {
            if (in_array(1062, (array) $e->errorInfo, true)) {
                Session::flash('error', 'Este e-mail já está em uso. Tente outro.');
            } else {
                Logger::error("Erro ao atualizar próprio cadastro", ['error' => $e->getMessage()]);
                Session::flash('error', 'Erro ao atualizar o cadastro. Tente novamente.');
            }
        }

        header('Location: ' . BASE_URL . '/perfil/completar_cadastro');
        exit;
    }

    /**
     * Forced password change (first access) or voluntary change from the profile menu.
     */
    public function trocar_senha() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require_once __DIR__ . '/../views/perfil/trocar_senha.php';
            return;
        }

        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Token de segurança inválido');
            header('Location: ' . BASE_URL . '/perfil/trocar_senha');
            exit;
        }

        $id = (int) Session::get('usuario_id');
        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            Session::flash('error', 'Usuário não encontrado.');
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        $validator = new Validator($_POST);
        $validator->required('senha_atual')
                  ->required('nova_senha')
                  ->min('nova_senha', 8)
                  ->password('nova_senha')
                  ->required('confirmar_senha')
                  ->matches('confirmar_senha', 'nova_senha');

        if ($validator->fails()) {
            Session::flash('error', $validator->getFirstError());
            header('Location: ' . BASE_URL . '/perfil/trocar_senha');
            exit;
        }

        $data = $validator->getData();

        if (!password_verify($data['senha_atual'], $usuario['senha'])) {
            Session::flash('error', 'A senha atual está incorreta.');
            header('Location: ' . BASE_URL . '/perfil/trocar_senha');
            exit;
        }

        $ok = $this->usuarioModel->trocarSenha($id, $data['nova_senha']);

        if ($ok) {
            Session::remove('forcar_troca_senha');
            Logger::audit(Constants::AUDITORIA_ACAO_REDEFINIR_SENHA, ['usuario_id' => $id]);
            (new Auditoria())->registrar(
                Constants::AUDITORIA_ACAO_REDEFINIR_SENHA,
                "{$usuario['nome']} alterou a senha pelo perfil",
                ['usuario_id' => $id]
            );
            Session::flash('success', 'Senha alterada com sucesso.');
            header('Location: ' . BASE_URL . '/perfil/completar_cadastro');
            exit;
        }

        Session::flash('error', 'Não foi possível alterar a senha. Tente novamente.');
        header('Location: ' . BASE_URL . '/perfil/trocar_senha');
        exit;
    }
}
