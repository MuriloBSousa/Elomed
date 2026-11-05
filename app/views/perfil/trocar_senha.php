<?php
require_once __DIR__ . '/../../middleware/CSRF.php';
require_once __DIR__ . '/../../middleware/Session.php';
Session::start();

$perfil = Session::get('usuario_perfil');
$perfilInicial = perfil_meta($perfil);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1><i class="bi bi-key text-primary"></i> Alterar Senha</h1>
        <p>Por segurança, defina uma nova senha antes de continuar.</p>
    </header>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="perfil-card text-center">
                <div class="avatar avatar-lg mx-auto"><i class="bi <?= $perfilInicial[1] ?>"></i></div>
                <h3 class="h5 fw-bold mt-3 mb-0"><?= e(Session::get('usuario_nome')) ?></h3>
                <p class="text-muted small mb-0"><?= $perfilInicial[0] ?></p>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="table-container">
                <h2><i class="bi bi-shield-lock text-primary"></i> Definir Nova Senha</h2>
                <p class="text-muted small mb-3">A senha deve ter pelo menos 8 caracteres, incluindo letras e números.</p>
                <form method="post" action="<?= BASE_URL ?>/perfil/trocar_senha">
                    <?= CSRF::getField() ?>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Senha atual</label>
                            <input type="password" name="senha_atual" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Nova senha</label>
                            <input type="password" name="nova_senha" class="form-control" required minlength="8"
                                   placeholder="Mínimo 8 caracteres, com letras e números">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Confirmar nova senha</label>
                            <input type="password" name="confirmar_senha" class="form-control" required minlength="8">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-check-lg me-1"></i> Salvar nova senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
