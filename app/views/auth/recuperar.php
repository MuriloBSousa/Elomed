<?php
Session::start();
$email        = $email ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EloMed - Recuperar Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/Assets/css/app.css">
</head>
<body class="auth-page">
    <div class="auth-shell">

        <aside class="brand-panel" aria-hidden="true">
            <div class="brand-content">
                <span class="brand-icon mx-auto"><i class="bi bi-key"></i></span>
                <span class="brand-wordmark mx-auto"><i class="bi bi-activity me-1"></i>EloMed<span class="dot"></span></span>
                <h1>Recuperar Senha</h1>
                <p>Informe o e-mail cadastrado e enviaremos um link para você redefinir sua senha.</p>
            </div>
            <div class="brand-footer">Cuidado e atendimento em casa &bull; EloMed</div>
        </aside>

        <section class="forms-stage">
            <div class="forms-track">
                <form class="auth-form signin-form active" action="<?= BASE_URL ?>/login/recuperar" method="POST" novalidate>
                    <?= CSRF::getField() ?>

                    <div class="auth-form-head">
                        <h1>Esqueceu a senha?</h1>
                        <p>Sem problemas. Vamos redefinir seu acesso.</p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="recEmail">E-mail cadastrado</label>
                        <input type="email" name="email" id="recEmail" class="form-control"
                               placeholder="seu@email.com" value="<?= e($email) ?>" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-submit">
                        <i class="bi bi-envelope-paper me-1"></i> Enviar link de recuperação
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
    <script src="<?= BASE_URL ?>/Assets/js/toasts.js"></script>
    <?php require_once __DIR__ . '/../../helpers/flash_toasts.php'; flash_toasts(); ?>
</body>
</html>