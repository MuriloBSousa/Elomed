<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Usuários - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Lista de Usuários</h1>
        <p>Gerencie os usuários do sistema.</p>
    </header>

    <div class="table-container">
        <h2><i class="bi bi-funnel text-primary"></i> Busca</h2>
        <?php include __DIR__ . '/../pesquisa.php'; ?>
    </div>

    <?php include __DIR__ . '/content.php'; ?>

    <div class="d-flex gap-2 mb-3">
        <a href="<?= BASE_URL ?>/usuario/cadastrar" class="btn btn-primary btn-sm">Novo Usuário</a>
        <a href="<?= BASE_URL ?>/usuario/cadastrar_profissional" class="btn btn-outline-primary btn-sm">Cadastrar Profissional</a>
        <a href="<?= BASE_URL ?>/usuario/cadastrar_paciente" class="btn btn-outline-primary btn-sm">Cadastrar Paciente</a>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>
