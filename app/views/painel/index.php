<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Principal - EloMed</title>
</head>
<body>
    <?php include __DIR__ . '/../header.php'; ?>

    <div class="main-container">
        <header class="page-header">
            <h1>Visão Geral Operacional</h1>
            <p>Bem-vindo ao centro de controle da clínica. Veja o andamento dos atendimentos domiciliares.</p>
        </header>

        <div class="metrics-grid">
            <div class="metric-card success">
                <span class="metric-icon"><i class="bi bi-people-fill"></i></span>
                <div>
                    <div class="metric-label">Pacientes Atendidos</div>
                    <div class="metric-value"><?= (int)$estatisticas['pacientes_atendidos'] ?></div>
                </div>
            </div>

            <div class="metric-card primary">
                <span class="metric-icon"><i class="bi bi-person-vcard-fill"></i></span>
                <div>
                    <div class="metric-label">Equipe em Campo</div>
                    <div class="metric-value"><?= (int)$estatisticas['equipe_campo'] ?></div>
                </div>
            </div>

            <div class="metric-card warning">
                <span class="metric-icon"><i class="bi bi-calendar2-check-fill"></i></span>
                <div>
                    <div class="metric-label">Visitas para Hoje</div>
                    <div class="metric-value"><?= (int)$estatisticas['visitas_hoje'] ?></div>
                </div>
            </div>

            <div class="metric-card error">
                <span class="metric-icon"><i class="bi bi-hourglass-split"></i></span>
                <div>
                    <div class="metric-label">Triagens Pendentes</div>
                    <div class="metric-value"><?= (int)$estatisticas['triagens_pendentes'] ?></div>
                </div>
            </div>

            <div class="metric-card primary">
                <span class="metric-icon"><i class="bi bi-stopwatch-fill"></i></span>
                <div>
                    <div class="metric-label">Tempo Médio de Atendimento</div>
                    <div class="metric-value"><?= (int) $estatisticas['tempo_medio'] ?> <small>min</small></div>
                </div>
            </div>

            <div class="metric-card warning">
                <span class="metric-icon"><i class="bi bi-bar-chart-fill"></i></span>
                <div>
                    <div class="metric-label">Ocupação da Agenda Hoje</div>
                    <div class="metric-value"><?= (int) $estatisticas['ocupacao_agenda'] ?>%</div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/painel/relatorio_pdf_agenda" target="_blank">
                <i class="bi bi-printer-fill me-1"></i> Imprimir Agenda em PDF
            </a>
            <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/painel/relatorio_pdf_triagens" target="_blank">
                <i class="bi bi-clipboard2-pulse me-1"></i> Triagens em PDF
            </a>
            <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/painel/relatorio_pdf_prontuarios" target="_blank">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Prontuários em PDF
            </a>
        </div>

        <div class="table-container">
            <h2><i class="bi bi-truck-front-fill text-primary"></i> Escala de Visitas em Andamento</h2>
            <p class="text-muted small">Acompanhe quais profissionais estão atendendo nas residências neste momento.</p>

            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Profissional</th>
                        <th>Especialidade/Função</th>
                        <th>Paciente</th>
                        <th>Horário Previsto</th>
                        <th>Status da Visita</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($escalaVisitas)): ?>
                        <tr>
                            <td colspan="5" class="sem-dados">
                                <i class="bi bi-inbox"></i> Nenhuma visita em andamento ou agendada para hoje.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($escalaVisitas as $visit): ?>
                            <tr>
                                <td><?= htmlspecialchars($visit['profissional_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($visit['especialidade'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($visit['paciente_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?= htmlspecialchars($visit['horario_inicio'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($visit['horario_fim'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <?php if ($visit['status'] === 'em_andamento'): ?>
                                        <span class="status-table em_andamento">Em Atendimento</span>
                                    <?php elseif ($visit['status'] === 'agendado'): ?>
                                        <span class="status-table agendado">Agendado</span>
                                    <?php else: ?>
                                        <span class="status-table"><?= htmlspecialchars($visit['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>