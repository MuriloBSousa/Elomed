<?php
Session::start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Editar Usuário</h1>
        <p>Atualize os dados do usuário.</p>
    </header>

    <form method="POST">
        <?= CSRF::getField() ?>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label" for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?= e($usuario['nome'] ?? '') ?>" required class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label" for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?= e($usuario['email'] ?? '') ?>" required class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label" for="senha">Nova Senha (Deixe em branco para manter a atual):</label>
                    <input type="password" id="senha" name="senha" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label" for="confirmar_senha">Confirmar Nova Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label" for="perfil">Perfil do Usuário:</label>
                    <?php $editandoProprio = (int)($usuario['id'] ?? 0) === (int)Session::get('usuario_id'); ?>
                    <select id="perfil" name="perfil" required class="form-select">
                        <option value="admin" <?= (isset($usuario['perfil']) && $usuario['perfil'] === 'admin') ? 'selected' : '' ?>>Administrador</option>
                        <option value="medico" <?= (isset($usuario['perfil']) && $usuario['perfil'] === 'medico') ? 'selected' : '' ?>>Médico</option>
                        <option value="enfermeiro" <?= (isset($usuario['perfil']) && $usuario['perfil'] === 'enfermeiro') ? 'selected' : '' ?>>Enfermeiro</option>
                        <option value="tecnico_enfermagem" <?= (isset($usuario['perfil']) && $usuario['perfil'] === 'tecnico_enfermagem') ? 'selected' : '' ?>>Técnico de Enfermagem</option>
                        <option value="paciente" <?= (isset($usuario['perfil']) && $usuario['perfil'] === 'paciente') ? 'selected' : '' ?>>Paciente</option>
                    </select>
                    <?php if ($editandoProprio): ?>
                        <div class="form-text">Você não pode alterar o próprio perfil de acesso.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Atualizar</button>
            <a href="<?= BASE_URL ?>/usuario/index" class="btn btn-outline-secondary btn-sm">Voltar</a>
        </div>
    </form>
</div>
</body>
</html>
