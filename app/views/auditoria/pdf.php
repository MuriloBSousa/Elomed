<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Trilha de Auditoria</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { color: #0369a1; border-bottom: 3px solid #0369a1; padding-bottom: 6px; font-size: 20px; }
        .filtros { margin-top: 10px; font-size: 11px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #e0f2fe; color: #075985; }
    </style>
</head>
<body>
    <h1>EloMed - Trilha de Auditoria</h1>
    <div class="filtros">
        Gerado em: <?= date('d/m/Y H:i:s') ?>
        <?php if (!empty($acao)): ?> | Evento: <?= htmlspecialchars($acao) ?><?php endif; ?>
        <?php if (!empty($por)):  ?> | Por: <?= htmlspecialchars($por) ?><?php endif; ?>
        <?php if (!empty($de)):   ?> | De: <?= htmlspecialchars($de) ?><?php endif; ?>
        <?php if (!empty($ate)):  ?> | Ate: <?= htmlspecialchars($ate) ?><?php endif; ?>
    </div>
    <table>
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
                <tr><td colspan="6">Nenhum registro.</td></tr>
            <?php else: ?>
                <?php foreach ($registros as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['data_hora']) ?></td>
                        <td><?= htmlspecialchars($record['usuario_nome'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($record['usuario_perfil'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($record['acao']) ?></td>
                        <td><?= htmlspecialchars($record['descricao']) ?></td>
                        <td><?= htmlspecialchars($record['ip'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>