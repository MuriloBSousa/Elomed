<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Paciente - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

    <div class="main-container">
        <?php if (!empty($cadastro_incompleto)): ?>
            <div class="alert alert-warning d-flex align-items-center flex-wrap gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span class="flex-grow-1"><strong>Seu cadastro está incompleto!</strong> Complete seus dados para solicitar atendimentos.</span>
                <a href="<?= BASE_URL ?>/paciente/completar_cadastro" class="btn btn-sm btn-warning fw-semibold">
                    <i class="bi bi-person-vcard me-1"></i> Completar Cadastro
                </a>
            </div>
        <?php endif; ?>

        <header class="page-header d-flex align-items-center gap-3 flex-wrap">
            <div>
                <h1>Painel do Paciente</h1>
                <p class="mb-0">Bem-vindo ao seu painel de controle</p>
            </div>
            <?php if (!empty($foto_paciente)): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($foto_paciente) ?>"
                     alt="Foto do paciente" class="avatar-photo ms-auto">
            <?php endif; ?>
        </header>

        <div class="metrics-grid">
            <div class="metric-card primary">
                <span class="metric-icon"><i class="bi bi-clipboard-check"></i></span>
                <div>
                    <div class="metric-label">Última Solicitação</div>
                    <?php if ($ultima_triagem): ?>
                        <div class="metric-value" style="font-size:1.1rem;"><?= e($ultima_triagem['nome_solicitante']) ?></div>
                        <?php
                            $triageStatus = $ultima_triagem['status'];
                            $badgeClass = 'badge-warning';
                            if ($triageStatus === 'aprovada') $badgeClass = 'badge-success';
                            elseif ($triageStatus === 'recusada') $badgeClass = 'badge-error';
                        ?>
                        <span class="badge <?= $badgeClass ?> mt-1"><?= ucfirst(e($triageStatus)) ?></span>
                    <?php else: ?>
                        <div class="metric-value sem-dados">Nenhuma triagem</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="metric-card success">
                <span class="metric-icon"><i class="bi bi-calendar-heart-fill"></i></span>
                <div>
                    <div class="metric-label">Próximo Atendimento</div>
                    <?php if (is_array($proximo_agendamento) && $proximo_agendamento['status'] !== 'pendente' && $proximo_agendamento['status'] !== 'cancelado'): ?>
                        <div class="metric-value" style="font-size: 1rem;"><?= e($proximo_agendamento['nome_profissional'] ?? '') ?></div>
                        <div class="metric-sub">
                            <?php
                                $apptDate = $proximo_agendamento['data_agendamento'] ?? null;
                                echo $apptDate ? date('d/m/Y', strtotime($apptDate)) : '';
                            ?>
                            às <?= e(substr($proximo_agendamento['horario_inicio'] ?? '', 0, 5)) ?>
                        </div>
                        <span class="badge badge-success mt-1">Confirmado</span>
                    <?php else: ?>
                        <div class="metric-value sem-dados">Nenhum agendamento</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="metric-card warning">
                <span class="metric-icon"><i class="bi bi-lightning-charge-fill"></i></span>
                <div>
                    <div class="metric-label">Ação Rápida</div>
                    <div class="metric-value" style="font-size: 1rem;">Solicitar Agendamento</div>
                    <a href="<?= BASE_URL ?>/agendamento/solicitar_agendamento" class="btn btn-primary btn-sm mt-2">
                        <i class="bi bi-plus-circle me-1"></i> Solicitar
                    </a>
                </div>
            </div>
        </div>

        <div class="table-container">
            <h2><i class="bi bi-calendar-event text-success"></i> Agenda de Compromissos</h2>
            <?php if (empty($agendamentos_futuros)): ?>
                <p class="sem-dados"><i class="bi bi-calendar-x"></i> Nenhum agendamento futuro encontrado.</p>
            <?php else: ?>
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Profissional</th>
                        <th>Especialidade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendamentos_futuros as $appointment):
                        $dateFormatted = date('d/m/Y', strtotime($appointment['data_agendamento']));
                        $timeRange = substr($appointment['horario_inicio'], 0, 5) . ' - ' . substr($appointment['horario_fim'], 0, 5);
                        $statusValue = $appointment['status'];
                        $badgeClass = $statusValue;
                    ?>
                    <tr>
                        <td><?= e($dateFormatted) ?></td>
                        <td><?= e($timeRange) ?></td>
                        <td><?= e($appointment['nome_profissional']) ?></td>
                        <td><?= e($appointment['especialidade']) ?></td>
                        <td><span class="status-table <?= e($badgeClass) ?>"><?= ucfirst(e($statusValue)) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
