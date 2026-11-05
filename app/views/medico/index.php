<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Profissional - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

    <div class="main-container">
        <header class="page-header">
            <?php foreach ($nome_profissional as $nome): ?>
                <h1>Bem-vindo(a), Dr(a). <?= e($nome['nome_profissional'] ?? 'Não informado') ?></h1>
                <p class="mb-0">
                    <span class="badge badge-info"><i class="bi bi-patch-check-fill me-1"></i><?= e($nome['numero_registro'] ?? 'Não informado') ?></span>
                </p>
            <?php endforeach; ?>
        </header>

        <div class="metrics-grid">
            <div class="metric-card primary">
                <span class="metric-icon"><i class="bi bi-calendar2-week-fill"></i></span>
                <div>
                    <div class="metric-label">Visitas Agendadas Hoje</div>
                    <div class="metric-value"><?= e($visitas_hoje[0]['total_visitas'] ?? 0) ?></div>
                </div>
            </div>
            <div class="metric-card success">
                <span class="metric-icon"><i class="bi bi-check2-circle"></i></span>
                <div>
                    <div class="metric-label">Visitas Concluídas</div>
                    <div class="metric-value"><?= e($visitas_concluidas_hoje[0]['visitas_concluidas'] ?? 0) ?></div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <h2><i class="bi bi-list-check text-primary"></i> Fila de Atendimentos de Hoje</h2>
            <?php if (isset($consulta_hoje) && is_array($consulta_hoje) && !empty($consulta_hoje)): ?>
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Paciente</th>
                        <th>Status</th>
                        <th>Atendimento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consulta_hoje as $con):
                        $rawDate = $con['data_agendamento'] ?? null;
                        $dateFormatted = $rawDate ? date('d/m/Y', strtotime($rawDate)) : 'N/A';

                        $startTime = substr($con['horario_inicio'] ?? '', 0, 5);
                        $endTime = substr($con['horario_fim'] ?? '', 0, 5);
                        $timeRange = ($startTime && $endTime) ? "$startTime - $endTime" : 'Não informado';

                        $statusValue = $con['status'] ?? 'indisponivel';
                    ?>
                    <tr>
                        <td><?= e($dateFormatted) ?></td>
                        <td><?= e($timeRange) ?></td>
                        <td><?= e($con['nome_paciente'] ?? 'Não informado') ?></td>
                        <td><span class="status-table <?= e($statusValue) ?>"><?= ucfirst(e($statusValue)) ?></span></td>
                        <td>
                            <?php if ($statusValue === 'agendado'): ?>
                                <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/medico/iniciar_atendimento/<?= (int)$con['id'] ?>">
                                    <i class="bi bi-play-fill me-1"></i> Iniciar Atendimento
                                </a>
                            <?php elseif ($statusValue === 'em_andamento'): ?>
                                <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/medico/iniciar_atendimento/<?= (int)$con['id'] ?>">
                                    <i class="bi bi-journal-medical me-1"></i> Continuar Atendimento
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="sem-dados"><i class="bi bi-calendar-x"></i> Nenhum atendimento agendado para hoje</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
