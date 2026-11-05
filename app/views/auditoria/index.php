<?php require_once __DIR__ . '/../../middleware/CSRF.php';
      require_once __DIR__ . '/../../middleware/Session.php';
      Session::start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trilha de Auditoria - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Trilha de Auditoria</h1>
        <p>Quem fez o que e quando. Filtre e baixe o relatorio em PDF.</p>
    </header>

    <form method="get" action="<?= BASE_URL ?>/auditoria/index">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Evento:</label>
                    <select name="acao" class="form-select">
                        <option value="">Todos</option>
                    <?php foreach ($acoesDisponiveis as $action): ?>
                            <option value="<?= e($action) ?>" <?= ($acao === $action) ? 'selected' : '' ?>>
                                <?= e($action) ?>
                            </option>
                    <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Por (nome ou perfil):</label>
                    <input type="text" name="por" class="form-control" value="<?= e($por) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label class="form-label">De:</label>
                    <input type="date" name="data_inicio" class="form-control" value="<?= e($de) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="mb-3">
                    <label class="form-label">Até:</label>
                    <input type="date" name="data_fim" class="form-control" value="<?= e($ate) ?>">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                    <a class="btn btn-outline-primary btn-sm" target="_blank"
                       href="<?= BASE_URL ?>/auditoria/pdf?acao=<?= urlencode($acao) ?>&amp;por=<?= urlencode($por) ?>&amp;data_inicio=<?= urlencode($de) ?>&amp;data_fim=<?= urlencode($ate) ?>">PDF</a>
                </div>
            </div>
        </div>
    </form>

    <div class="table-container">
        <h2><i class="bi bi-shield-check text-primary"></i> Registros</h2>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Quando</th>
                    <th>Quem</th>
                    <th>Perfil</th>
                    <th>Evento</th>
                    <th>Descricao</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="6" class="sem-dados"><i class="bi bi-inbox"></i> Nenhum registro para os filtros usados.</td></tr>
                <?php else: ?>
                    <?php foreach ($registros as $record): ?>
                        <tr>
                            <td><?= e($record['data_hora']) ?></td>
                            <td><?= e($record['usuario_nome'] ?? '-') ?></td>
                            <td><?= e($record['usuario_perfil'] ?? '-') ?></td>
                            <td><span class="badge badge-primary"><?= e($record['acao']) ?></span></td>
                            <td><?= e($record['descricao']) ?>
                                <?php if (!empty($record['propriedades'])): ?>
                                    <br><small><?= e($record['propriedades']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($record['ip'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= $pagination->render() ?>
</div>
</body>
</html>
