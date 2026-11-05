<?php
Session::start();
$modo = $modo ?? 'login';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EloMed - Entrar ou Cadastrar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/Assets/css/app.css">
</head>
<body class="auth-page">
    <div class="auth-shell <?= $modo === 'cadastro' ? 'signup' : '' ?>" id="authShell">

        <!-- BRAND PANEL (slides from side to side) -->
        <aside class="brand-panel" id="brandPanel" aria-hidden="true">
            <div class="brand-content">
                <span class="brand-icon mx-auto"><i class="bi bi-heart-pulse"></i></span>
                <span class="brand-wordmark mx-auto"><i class="bi bi-activity me-1"></i>EloMed<span class="dot"></span></span>
                <h1 id="brandTitle">Bem-vindo de volta!</h1>
                <p id="brandText">Acesse sua conta para cuidar da sua saúde e acompanhar atendimentos domiciliares.</p>
                <button type="button" class="btn" id="brandAction" onclick="toggleAuth()">
                    <i class="bi bi-person-plus me-1" id="brandActionIcon"></i>
                    <span id="brandActionText">Não tem uma conta?</span>
                    <strong id="brandActionStrong"> Cadastre-se</strong>
                </button>
            </div>
            <div class="brand-footer">Cuidado e atendimento em casa &bull; EloMed</div>
        </aside>

        <!-- FORMS PANEL -->
        <section class="forms-stage">
            <div class="forms-track">

                <!-- SIGN IN -->
                <form class="auth-form signin-form <?= $modo === 'login' ? 'active' : '' ?>" action="<?= BASE_URL ?>/login/logar" method="POST" novalidate>
                    <?= CSRF::getField() ?>

                    <div class="auth-form-head">
                        <h1>Acesso ao Sistema</h1>
                        <p>Entre com suas credenciais EloMed</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="loginEmail">E-mail</label>
                        <input type="email" name="email" id="loginEmail" class="form-control" placeholder="seu@email.com" required
                               oninput="checkEmail(this, 'loginEmailFeedback', 'login')">
                        <div class="email-feedback" id="loginEmailFeedback">
                            <i class="bi"></i> <span></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="loginSenha">Senha</label>
                        <div class="input-password">
                            <input type="password" name="senha" id="loginSenha" class="form-control" placeholder="Sua senha" required>
                            <button type="button" class="btn-password" tabindex="-1" onclick="verSenha('loginSenha', this)" aria-label="Mostrar senha">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <a href="<?= BASE_URL ?>/login/recuperar" class="link-recuperar small">
                                <i class="bi bi-key me-1"></i>Esqueci minha senha
                            </a>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-submit">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
                    </button>

                    <div class="auth-divider"><span>ou</span></div>

                    <a href="<?= BASE_URL ?>/login/google" class="btn btn-google">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                        Entrar com Google
                    </a>

                    <p class="auth-help">
                        <i class="bi bi-shield-check me-1"></i> Acesso seguro · Seus dados estão protegidos
                    </p>

                    <p class="auth-help">
                        <a href="<?= BASE_URL ?>/home/index" class="link-recuperar">
                            <i class="bi bi-arrow-left me-1"></i> Voltar à página inicial
                        </a>
                    </p>
                </form>

                <!-- SIGN UP -->
                <form class="auth-form signup-form <?= $modo === 'cadastro' ? 'active' : '' ?>" action="<?= BASE_URL ?>/cadastro/cadastrar" method="POST" novalidate>
                    <?= CSRF::getField() ?>

                    <div class="auth-form-head">
                        <h1>Criar Conta</h1>
                        <p>Cadastro rápido e seguro no EloMed</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="cadNome">Nome completo</label>
                        <input type="text" name="nome" id="cadNome" class="form-control" placeholder="Seu nome completo" required minlength="3">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="cadEmail">E-mail</label>
                        <input type="email" name="email" id="cadEmail" class="form-control" placeholder="seu@email.com" required
                               oninput="checkEmail(this, 'cadEmailFeedback', 'cadastro')">
                        <div class="email-feedback" id="cadEmailFeedback">
                            <i class="bi"></i> <span></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="cadastroSenha">Senha</label>
                        <div class="input-password">
                            <input type="password" name="senha" id="cadastroSenha" class="form-control" placeholder="Mín. 8, com maiúscula, número e símbolo" required>
                            <button type="button" class="btn-password" tabindex="-1" onclick="verSenha('cadastroSenha', this)" aria-label="Mostrar senha">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text small">Pelo menos 8 caracteres, com letra maiúscula, número e caractere especial.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="cadConfirma">Confirmar senha</label>
                        <div class="input-password">
                            <input type="password" name="confirmar_senha" id="cadConfirma" class="form-control" placeholder="Repita a senha" required>
                            <button type="button" class="btn-password" tabindex="-1" onclick="verSenha('cadConfirma', this)" aria-label="Mostrar senha">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="cadPerfil">Perfil de acesso</label>
                        <select name="perfil" id="cadPerfil" class="form-select" required>
                            <option value="medico">Médico</option>
                            <option value="enfermeiro">Enfermeiro</option>
                            <option value="tecnico_enfermagem">Técnico de Enfermagem</option>
                            <option value="paciente" selected>Paciente</option>
                        </select>
                    </div>

                    <div class="alert alert-info small py-2 d-flex" role="alert">
                        <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                        <span>Escolha o perfil que corresponde à sua função. O perfil <strong>Administrador</strong> não está disponível para cadastro automático.</span>
                    </div>

                    <button type="submit" class="btn btn-submit">
                        <i class="bi bi-check-lg me-1"></i> Cadastrar
                    </button>

                    <p class="auth-help">
                        <i class="bi bi-shield-check me-1"></i> Seus dados estão seguros com a EloMed
                    </p>
                </form>

            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/Assets/js/toasts.js"></script>
    <script>
    const reEmail = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    function showFeedback(id, type, msg) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('visivel', !!msg);
        if (!msg) {
            el.classList.remove('text-success', 'text-danger');
            return;
        }
        el.classList.remove('text-success', 'text-danger');
        el.classList.add(type === 'ok' ? 'text-success' : 'text-danger');
        el.querySelector('i').className = 'bi ' + (type === 'ok' ? 'bi-check-circle-fill' : 'bi-x-circle-fill');
        el.querySelector('span').textContent = msg;
    }

    // Format-only validation. Availability is deliberately NOT checked here:
    // a public "does this e-mail exist" lookup would let anyone enumerate
    // registered patients and staff. The server reports duplicates on submit.
    function checkEmail(input, feedId, mode) {
        const email = input.value.trim();

        if (email === '') { showFeedback(feedId, '', ''); return; }
        if (!reEmail.test(email)) {
            showFeedback(feedId, 'err', 'Digite um e-mail válido.');
            return;
        }
        showFeedback(feedId, 'ok', 'Informe sua senha para continuar.');
    }

    function verSenha(id, btn) {
        const input = document.getElementById(id);
        const olho = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            olho.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            olho.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }

    const texts = {
        login: {
            title:   'Bem-vindo de volta!',
            text:    'Acesse sua conta para cuidar da sua saúde e acompanhar atendimentos domiciliares.',
            icon:    'bi-person-plus',
            span:    'Não tem uma conta?',
            strong:  ' Cadastre-se'
        },
        cadastro: {
            title:   'Criar Conta',
            text:    'Junte-se à EloMed em poucos segundos e solicite atendimentos domiciliares.',
            icon:    'bi-box-arrow-in-right',
            span:    'Já tem uma conta?',
            strong:  ' Acesse'
        }
    };

    function toggleAuth(para) {
        const shell  = document.getElementById('authShell');
        const signup = para ? (para === 'cadastro') : !shell.classList.contains('signup');

        shell.classList.toggle('signup', signup);

        document.querySelectorAll('.auth-form').forEach(f => {
            f.classList.toggle('active',
                signup ? f.classList.contains('signup-form') : f.classList.contains('signin-form'));
        });

        const t = texts[signup ? 'cadastro' : 'login'];
        document.getElementById('brandTitle').textContent       = t.title;
        document.getElementById('brandText').textContent        = t.text;
        document.getElementById('brandActionIcon').className    = 'bi ' + t.icon;
        document.getElementById('brandActionText').textContent  = t.span;
        document.getElementById('brandActionStrong').textContent = t.strong;
    }
    </script>
    <?php require_once __DIR__ . '/../../helpers/flash_toasts.php'; flash_toasts(); ?>
</body>
</html>