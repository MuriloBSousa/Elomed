<?php
Session::start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Cadastro de Usuário</h1>
        <p>Registre um novo usuário no sistema.</p>
    </header>

    <?php if (isset($erro)): ?>
        <div class="alert alert-danger"><?= e($erro ?? "") ?></div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/usuario/cadastrar" method="POST">
        <?= CSRF::getField() ?>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Nome:</label>
                    <input type="text" name="nome" required class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Email:</label>
                    <input type="text" name="email" required class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Senha:</label>
                    <input type="password" name="senha" required class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Perfil:</label>
                    <select name="perfil" class="form-select">
                        <option value="admin">Administrador</option>
                        <option value="medico">Médico</option>
                        <option value="enfermeiro">Enfermeiro</option>
                        <option value="tecnico_enfermagem">Tec.Enfermagem</option>
                        <option value="paciente">Paciente</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Cadastrar</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.href='<?= BASE_URL ?>/usuario/index'">Voltar</button>
        </div>
    </form>
</div>
</body>
</html>
