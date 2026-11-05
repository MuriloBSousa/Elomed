<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Prontuarios - EloMed</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { color: #0369a1; border-bottom: 3px solid #0369a1; padding-bottom: 6px; }
        .filtros { margin-top: 10px; font-size: 11px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #e0f2fe; color: #075985; }
    </style>
</head>
<body>
    <h1>EloMed - Prontuarios (<?= htmlspecialchars($data) ?>)</h1>
    <div class="filtros">Gerado em: <?= date('d/m/Y H:i:s') ?> | Total: <?= (int) count($prontuarios) ?></div>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Profissional</th>
                <th>Paciente</th>
                <th>Diagnostico Final</th>
                <th>Conduta Medica</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($prontuarios)): ?>
                <tr><td colspan="5">Nenhum prontuario registrado.</td></tr>
            <?php else: ?>
                <?php foreach ($prontuarios as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['data_agendamento']) ?></td>
                        <td><?= htmlspecialchars($record['profissional_nome']) ?></td>
                        <td><?= htmlspecialchars($record['paciente_nome']) ?></td>
                        <td><?= htmlspecialchars($record['diagnostico_final'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($record['conduta_medica'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>