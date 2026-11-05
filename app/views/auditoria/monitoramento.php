<?php require_once __DIR__ . '/../../middleware/Session.php';
      Session::start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoramento - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1><i class="bi bi-graph-up-arrow text-primary"></i> Monitoramento do Sistema</h1>
        <p>Quem entrou, quando saiu, o que alterou e quais visitas foram realizadas.</p>
    </header>

    <div class="metrics-grid">
        <div class="metric-card primary">
            <span class="metric-icon"><i class="bi bi-people-fill"></i></span>
            <div>
                <div class="metric-label">Usuários Ativos</div>
                <div class="metric-value"><?= (int) $stats['usuarios_ativos'] ?></div>
            </div>
        </div>
        <div class="metric-card success">
            <span class="metric-icon"><i class="bi bi-box-arrow-in-right"></i></span>
            <div>
                <div class="metric-label">Logins Hoje</div>
                <div class="metric-value"><?= (int) $stats['logins_hoje'] ?></div>
            </div>
        </div>
        <div class="metric-card warning">
            <span class="metric-icon"><i class="bi bi-clipboard2-pulse-fill"></i></span>
            <div>
                <div class="metric-label">Visitas Concluídas</div>
                <div class="metric-value"><?= (int) $stats['visitas'] ?></div>
            </div>
        </div>
        <div class="metric-card error">
            <span class="metric-icon"><i class="bi bi-shield-check"></i></span>
            <div>
                <div class="metric-label">Eventos Registrados</div>
                <div class="metric-value"><?= (int) $stats['acoes'] ?></div>
            </div>
        </div>
    </div>

    <form method="get" action="<?= BASE_URL ?>/auditoria/monitoramento">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">Por (nome ou perfil):</label>
                    <input type="text" name="por" class="form-control" value="<?= e($por) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">De:</label>
                    <input type="date" name="data_inicio" class="form-control" value="<?= e($de) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Até:</label>
                    <input type="date" name="data_fim" class="form-control" value="<?= e($ate) ?>">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/auditoria/index">
            <i class="bi bi-journal-text me-1"></i> Ver Trilha Completa de Auditoria
        </a>
        <a class="btn btn-outline-primary btn-sm" href="<?= BASE_URL ?>/usuario/index">
            <i class="bi bi-people me-1"></i> Gerenciar Usuários
        </a>
    </div>

    <div class="table-container">
        <h2><i class="bi bi-box-arrow-in-right text-primary"></i> Acessos — Entrada e Saída por Dia</h2>
        <?php if (empty($sessoes)): ?>
            <p class="sem-dados"><i class="bi bi-inbox"></i> Nenhum acesso encontrado para os filtros usados.</p>
        <?php else: ?>
        <table class="table align-middle table-responsive-md">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Perfil</th>
                    <th>Dia</th>
                    <th>Entrada</th>
                    <th>Saída</th>
                    <th>Permanência</th>
                    <th>Situação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessoes as $session): ?>
                <tr>
                    <td class="fw-semibold"><?= e($session['usuario_nome']) ?></td>
                    <td><?= e($session['usuario_perfil']) ?></td>
                    <td><?= e(date('d/m/Y', strtotime($session['dia']))) ?></td>
                    <td><?= $session['primeira_entrada'] ? e(date('H:i', strtotime($session['primeira_entrada']))) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= $session['ultima_saida'] ? e(date('H:i', strtotime($session['ultima_saida']))) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= $session['permanencia'] !== '' ? e($session['permanencia']) : '<span class="text-muted">—</span>' ?></td>
                    <td>
                        <?php if ($session['concluida']): ?>
                            <span class="badge badge-success">Saiu do sistema</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Sem registro de saída</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="table-container">
        <h2><i class="bi bi-arrow-left-right text-primary"></i> Eventos de Acesso Recentes</h2>
        <?php if (empty($acessos)): ?>
            <p class="sem-dados"><i class="bi bi-inbox"></i> Nenhum evento de acesso encontrado.</p>
        <?php else: ?>
        <table class="table align-middle table-responsive-md">
            <thead>
                <tr>
                    <th>Quando</th>
                    <th>Usuário</th>
                    <th>Perfil</th>
                    <th>Evento</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($acessos as $access): ?>
                <tr>
                    <td><?= e(date('d/m/Y H:i:s', strtotime($access['data_hora']))) ?></td>
                    <td class="fw-semibold"><?= e($access['usuario_nome'] ?? '-') ?></td>
                    <td><?= e($access['usuario_perfil'] ?? '-') ?></td>
                    <td>
                        <?php if ($access['acao'] === 'login'): ?>
                            <span class="badge badge-success">Entrada (login)</span>
                        <?php else: ?>
                            <span class="badge badge-default">Saída (logout)</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($access['ip'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="table-container">
        <h2><i class="bi bi-clipboard2-pulse text-primary"></i> Visitas Realizadas</h2>
        <?php if (empty($visitas)): ?>
            <p class="sem-dados"><i class="bi bi-inbox"></i> Nenhuma visita concluída encontrada.</p>
        <?php else: ?>
        <table class="table align-middle table-responsive-md">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Profissional</th>
                    <th>Especialidade</th>
                    <th>Paciente</th>
                    <th>Diagnóstico</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visitas as $visit): ?>
                <tr>
                    <td><?= e(date('d/m/Y', strtotime($visit['data_agendamento']))) ?></td>
                    <td><?= e(substr($visit['horario_inicio'], 0, 5)) ?> - <?= e(substr($visit['horario_fim'], 0, 5)) ?></td>
                    <td class="fw-semibold"><?= e($visit['nome_profissional']) ?></td>
                    <td><?= e($visit['especialidade']) ?></td>
                    <td><?= e($visit['nome_paciente']) ?></td>
                    <td><?= !empty(trim($visit['diagnostico_final'] ?? '')) ? e(mb_strimwidth(trim($visit['diagnostico_final']), 0, 60, '...')) : '<span class="badge badge-default">Sem diagnóstico</span>' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>