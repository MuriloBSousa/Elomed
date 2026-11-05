<?php
Session::start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EloMed - Redefinir Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/Assets/css/app.css">
</head>
<body class="auth-page">
    <div class="auth-shell">

        <aside class="brand-panel" aria-hidden="true">
            <div class="brand-content">
                <span class="brand-icon mx-auto"><i class="bi bi-shield-lock"></i></span>
                <span class="brand-wordmark mx-auto"><i class="bi bi-activity me-1"></i>EloMed<span class="dot"></span></span>
                <h1>Redefinir Senha</h1>
                <p>Escolha uma nova senha. Você poderá acessar a plataforma logo em seguida.</p>
            </div>
            <div class="brand-footer">Cuidado e atendimento em casa &bull; EloMed</div>
        </aside>

        <section class="forms-stage">
            <div class="forms-track">
                <form class="auth-form signin-form active" action="<?= BASE_URL ?>/login/redefinir/<?= e($token) ?>" method="POST" novalidate>
                    <?= CSRF::getField() ?>

                    <div class="auth-form-head">
                        <h1>Nova senha</h1>
                        <p>Defina uma senha forte e diferente das anteriores.</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="redefSenha">Nova senha</label>
                        <div class="input-password">
                            <input type="password" name="senha" id="redefSenha" class="form-control"
                                   placeholder="Mín. 8, com maiúscula, número e símbolo" required>
                            <button type="button" class="btn-password" tabindex="-1" onclick="verSenha('redefSenha', this)" aria-label="Mostrar senha">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text small">Pelo menos 8 caracteres, com letra maiúscula, número e caractere especial.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="redefConfirma">Confirmar nova senha</label>
                        <div class="input-password">
                            <input type="password" name="confirmar_senha" id="redefConfirma" class="form-control"
                                   placeholder="Repita a nova senha" required>
                            <button type="button" class="btn-password" tabindex="-1" onclick="verSenha('redefConfirma', this)" aria-label="Mostrar senha">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-submit">
                        <i class="bi bi-check-lg me-1"></i> Redefinir senha
                    </button>

                    <p class="auth-help">
                        <a href="<?= BASE_URL ?>/login/index" class="link-recuperar">
                            <i class="bi bi-box-arrow-in-left me-1"></i> Voltar ao login
                        </a>
                    </p>
                </form>
            </div>
        </section>
    </div>

    <script>
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
    </script>
    <script src="<?= BASE_URL ?>/Assets/js/toasts.js"></script>
    <?php require_once __DIR__ . '/../../helpers/flash_toasts.php'; flash_toasts(); ?>
</body>
</html>