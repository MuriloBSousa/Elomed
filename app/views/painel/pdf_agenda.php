<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Agenda do Dia - EloMed</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { color: #0369a1; border-bottom: 3px solid #0369a1; padding-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #e0f2fe; color: #075985; }
    </style>
</head>
<body>
    <h1>EloMed - Agenda do Dia (<?= htmlspecialchars($data) ?>)</h1>
    <table>
        <thead>
            <tr>
                <th>Profissional</th>
                <th>Especialidade</th>
                <th>Paciente</th>
                <th>Horario</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($escala)): ?>
                <tr><td colspan="5">Nenhum agendamento.</td></tr>
            <?php else: ?>
                <?php foreach ($escala as $visit): ?>
                    <tr>
                        <td><?= htmlspecialchars($visit['profissional_nome']) ?></td>
                        <td><?= htmlspecialchars($visit['especialidade']) ?></td>
                        <td><?= htmlspecialchars($visit['paciente_nome']) ?></td>
                        <td><?= htmlspecialchars($visit['horario_inicio']) ?> - <?= htmlspecialchars($visit['horario_fim']) ?></td>
                        <td><?= htmlspecialchars($visit['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>