<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Agenda - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Minha Agenda</h1>
        <p>Gerencie seus agendamentos futuros.</p>
    </header>

    <div class="table-container">
        <h2><i class="bi bi-calendar-check text-primary"></i> Agendamentos Futuros</h2>
        <?php if (empty($agendamentos_futuros)): ?>
            <p class="sem-dados"><i class="bi bi-inbox"></i> Nenhum agendamento futuro encontrado.</p>
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
                <?php foreach ($agendamentos_futuros as $appointment):
                    $dateFormatted = date('d/m/Y', strtotime($appointment['data_agendamento']));
                    $timeRange = substr($appointment['horario_inicio'], 0, 5) . ' - ' . substr($appointment['horario_fim'], 0, 5);
                    $statusValue = $appointment['status'];
                ?>
                <tr>
                    <td><?= e($dateFormatted) ?></td>
                    <td><?= e($timeRange) ?></td>
                    <td><?= e($appointment['nome_paciente']) ?></td>
                    <td><?= e($appointment['especialidade']) ?></td>
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
