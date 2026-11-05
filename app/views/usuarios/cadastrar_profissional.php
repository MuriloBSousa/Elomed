<?php
Session::start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Profissional - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Cadastrar Profissional</h1>
        <p>Busque um usuário existente e configure como profissional de saúde.</p>
    </header>

    <?php if (isset($erro)): ?>
        <div class="alert alert-danger"><?= e($erro ?? "") ?></div>
    <?php endif; ?>

    <!-- 1. SEARCH BLOCK -->
    <div class="mb-3">
        <form action="<?= BASE_URL ?>/usuario/procurar" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label" for="busca-nome">Buscar Usuário:</label>
                    <input id="busca-nome" class="form-control" type="search" name="nome" placeholder="Digite o nome..." autocomplete="off" value="<?= isset($_GET['nome']) ? e($_GET['nome']) : '' ?>">
                    <datalist id="lista-usuarios">
                        <?php if (!empty($todosOsUsuarios)): ?>
                            <?php foreach ($todosOsUsuarios as $todUnico): ?>
                                <option value="<?= e($todUnico['nome']) ?>"></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </datalist>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary btn-sm" type="submit">Buscar</button>
                </div>
            </div>
        </form>
    </div>

    <hr class="my-4">

    <!-- 2. CONDITIONAL PHP RENDERING FLOW -->
    <?php if (!empty($usuarios)): ?>

        <?php if (count($usuarios) > 1): ?>
            <!-- CASE A: More than one user found -->
            <div class="mb-3">
                <div class="alert alert-info">
                    Múltiplos usuários encontrados. Escolha qual deseja configurar:
                </div>
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Perfil</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= e($u['nome']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><?= e($u['perfil']) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/usuario/procurar?nome=<?= urlencode($u['nome']) ?>" class="btn btn-primary btn-sm">
                                        Selecionar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- CASE B: Only one user returned -->
            <?php $u = $usuarios[0]; ?>

            <form action="<?= BASE_URL ?>/usuario/cadastrar_profissional" method="POST" enctype="multipart/form-data">
                <?= CSRF::getField() ?>

                <input type="hidden" name="controller" value="usuario">
                <input type="hidden" name="action" value="cadastrar_profissional">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nome:</label>
                            <input type="text" name="nome" value="<?= e($u['nome']) ?>" readonly class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Email:</label>
                            <input type="text" name="email" value="<?= e($u['email']) ?>" readonly class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Perfil Atual:</label>
                            <input type="text" value="<?= e($u['perfil']) ?>" readonly class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Especialidade:</label>
                            <input type="text" name="especialidade" required class="form-control" placeholder="Ex: Médico Pediatra">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Número De Registro:</label>
                            <input type="text" name="numero_registro" required class="form-control" placeholder="Ex: CRM/DF 12345">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label">Foto de perfil <small class="text-muted">(JPG, PNG ou WEBP — máx. 2MB)</small></label>
                            <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Finalizar Cadastro</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.href='<?= BASE_URL ?>/usuario/index'">Voltar</button>
                </div>
            </form>
        <?php endif; ?>

    <?php else: ?>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.href='<?= BASE_URL ?>/usuario/index'">Voltar</button>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
