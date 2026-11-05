<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Meus Agendamentos</h1>
        <p>Visualize e gerencie seus agendamentos médicos.</p>
    </header>

    <div class="table-container">
        <h2><i class="bi bi-calendar2-check text-primary"></i> Agendamentos</h2>
        <?php if (empty($agendamentos)): ?>
            <p class="sem-dados"><i class="bi bi-inbox"></i> Nenhum agendamento encontrado.</p>
        <?php else: ?>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Profissional</th>
                    <th>Especialidade</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agendamentos as $appointment):
                    $dateFormatted = date('d/m/Y', strtotime($appointment['data_agendamento']));
                    $timeRange = substr($appointment['horario_inicio'], 0, 5) . ' - ' . substr($appointment['horario_fim'], 0, 5);
                    $statusValue = $appointment['status'];
                    $appointmentId = (int) $appointment['id'];
                    $vezesReagendado = (int) ($appointment['vezes_reagendado'] ?? 0);
                    $vindaDeTriagem = !empty($appointment['fk_triagem']);
                ?>
                <tr>
                    <td><?= e($dateFormatted) ?></td>
                    <td><?= e($timeRange) ?></td>
                    <td><?= e($appointment['nome_profissional']) ?></td>
                    <td><?= e($appointment['especialidade']) ?></td>
                    <td>
                        <span class="status-table <?= e($statusValue) ?>"><?= ucfirst(e($statusValue)) ?></span>
                        <?php if ($vindaDeTriagem): ?>
                            <small class="d-block text-muted"><i class="bi bi-clipboard2-pulse me-1"></i>Agendado pela equipe após sua triagem</small>
                        <?php elseif ($vezesReagendado > 0): ?>
                            <small class="d-block text-muted">Reagendado <?= $vezesReagendado ?>x</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$vindaDeTriagem
                                  && in_array($statusValue, ['pendente', 'agendado'], true)
                                  && $vezesReagendado < Constants::AGENDAMENTO_MAX_REAGENDAMENTOS
                                  && $appointment['data_agendamento'] >= date('Y-m-d')): ?>
                            <details class="reagendar-box">
                                <summary class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-arrow-repeat me-1"></i>Reagendar
                                </summary>
                                <form method="post" class="mt-2 p-2 border rounded bg-light"
                                      action="<?= BASE_URL ?>/paciente/reagendar_agendamento/<?= $appointmentId ?>">
                                    <?= CSRF::getField() ?>
                                    <div class="row g-2">
                                        <div class="col-md-12">
                                            <input type="date" name="data" class="form-control form-control-sm" required
                                                   value="<?= e($appointment['data_agendamento']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="time" name="horario_inicio" class="form-control form-control-sm" required
                                                   value="<?= e($appointment['horario_inicio']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="time" name="horario_fim" class="form-control form-control-sm" required
                                                   value="<?= e($appointment['horario_fim']) ?>">
                                        </div>
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                                Propor novo horário
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </details>
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
</div>
</body>
</html>
