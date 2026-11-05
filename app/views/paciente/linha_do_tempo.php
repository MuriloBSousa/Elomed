<?php require_once __DIR__ . '/../../middleware/Session.php'; Session::start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linha do Tempo - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Linha do Tempo do Atendimento</h1>
        <p>Agendamentos, triagens e visitas, do mais recente ao mais antigo.</p>
    </header>

    <div class="table-container">
        <h2><i class="bi bi-hourglass-split text-primary"></i> Linha do Tempo</h2>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Detalhe</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($linha)): ?>
                    <tr><td colspan="5" class="sem-dados"><i class="bi bi-inbox"></i> Nenhum registro ainda.</td></tr>
                <?php else: ?>
                    <?php foreach ($linha as $entry): ?>
                        <tr>
                            <td><?= e($entry['data_evento']) ?></td>
                            <td><?= e($entry['horario'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge-<?= $entry['tipo'] === 'triagem' ? 'info' : ($entry['tipo'] === 'visita' ? 'primary' : 'success') ?>">
                                    <?= e($entry['tipo']) ?>
                                </span>
                            </td>
                            <td><?= e($entry['status']) ?></td>
                            <td><?= e($entry['detalhe'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
