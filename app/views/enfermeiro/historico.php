<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Visitas - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Histórico de Visitas</h1>
        <p>Visualize todas as visitas concluídas realizadas.</p>
    </header>

    <div class="table-container">
        <h2><i class="bi bi-clock-history text-primary"></i> Visitas Concluídas</h2>
        <?php if (empty($visitas_concluidas)): ?>
            <p class="sem-dados"><i class="bi bi-inbox"></i> Nenhuma visita concluída encontrada.</p>
        <?php else: ?>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Paciente</th>
                    <th>Especialidade</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visitas_concluidas as $visit):
                    $dateFormatted = date('d/m/Y', strtotime($visit['data_agendamento']));
                    $timeRange = substr($visit['horario_inicio'], 0, 5) . ' - ' . substr($visit['horario_fim'], 0, 5);
                    $statusValue = $visit['status'];
                ?>
                <tr>
                    <td><?= e($dateFormatted) ?></td>
                    <td><?= e($timeRange) ?></td>
                    <td><?= e($visit['nome_paciente']) ?></td>
                    <td><?= e($visit['especialidade']) ?></td>
                    <td><span class="status-table <?= e($statusValue) ?>"><?= ucfirst(e($statusValue)) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
