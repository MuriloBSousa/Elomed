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
        <p>Gerencie seus agendamentos futuros e passados.</p>
    </header>

    <div class="table-container">
        <h2><i class="bi bi-calendar2-week text-primary"></i> Agendamentos Futuros</h2>
        <?php if (empty($agendamentos_futuros)): ?>
            <p class="sem-dados"><i class="bi bi-calendar-x"></i> Nenhum agendamento futuro encontrado.</p>
        <?php else: ?>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Paciente</th>
                    <th>Especialidade</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agendamentos_futuros as $appointment):
                    $dateFormatted = date('d/m/Y', strtotime($appointment['data_agendamento']));
                    $timeRange = substr($appointment['horario_inicio'], 0, 5) . ' - ' . substr($appointment['horario_fim'], 0, 5);
                    $statusValue = $appointment['status'];
                    $appointmentId = (int) ($appointment['agendamento_id'] ?? 0);
                ?>
                <tr>
                    <td><?= e($dateFormatted) ?></td>
                    <td><?= e($timeRange) ?></td>
                    <td><?= e($appointment['nome_paciente']) ?></td>
                    <td><?= e($appointment['especialidade']) ?></td>
                    <td><span class="status-table <?= e($statusValue) ?>"><?= ucfirst(e($statusValue)) ?></span></td>
                    <td>
                        <?php if ($statusValue === 'agendado'): ?>
                            <a class="btn btn-success btn-sm mb-1" href="<?= BASE_URL ?>/medico/iniciar_atendimento/<?= $appointmentId ?>">
                                <i class="bi bi-play-fill me-1"></i>Iniciar Visita
                            </a>
                            <form method="post" action="<?= BASE_URL ?>/medico/marcar_ausente/<?= $appointmentId ?>" class="d-inline">
                                <?= CSRF::getField() ?>
                                <button type="submit" class="btn btn-outline-warning btn-sm mb-1"
                                        onclick="return confirm('Marcar esse paciente como ausente?');">
                                    <i class="bi bi-person-x me-1"></i>Ausente
                                </button>
                            </form>
                        <?php elseif ($statusValue === 'em_andamento'): ?>
                            <a class="btn btn-primary btn-sm mb-1" href="<?= BASE_URL ?>/medico/iniciar_atendimento/<?= $appointmentId ?>">
                                <i class="bi bi-arrow-repeat me-1"></i>Continuar Atendimento
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="table-container">
        <h2><i class="bi bi-arrow-counterclockwise text-muted"></i> Agendamentos Passados</h2>
        <?php if (empty($agendamentos_passados)): ?>
            <p class="sem-dados"><i class="bi bi-calendar-x"></i> Nenhum agendamento passado encontrado.</p>
        <?php else: ?>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Paciente</th>
                    <th>Especialidade</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agendamentos_passados as $appointment):
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
                    <td><span class="text-muted small">-</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
