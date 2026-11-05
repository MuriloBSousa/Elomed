<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Triagens - EloMed</title>
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
    <h1>EloMed - Relatorio de Triagens (<?= htmlspecialchars($data) ?>)</h1>
    <div class="filtros">Gerado em: <?= date('d/m/Y H:i:s') ?> | Total: <?= (int) count($triagens) ?></div>
    <table>
        <thead>
            <tr>
                <th>Solicitante</th>
                <th>Paciente</th>
                <th>Classificacao</th>
                <th>Status</th>
                <th>Enviada em</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($triagens)): ?>
                <tr><td colspan="5">Nenhuma triagem registrada.</td></tr>
            <?php else: ?>
                <?php foreach ($triagens as $triage): ?>
                    <tr>
                        <td><?= htmlspecialchars($triage['nome_solicitante']) ?></td>
                        <td><?= htmlspecialchars($triage['paciente_nome']) ?></td>
                        <td><?= htmlspecialchars($triage['classificacao'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($triage['status']) ?></td>
                        <td><?= htmlspecialchars($triage['criado_em']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>