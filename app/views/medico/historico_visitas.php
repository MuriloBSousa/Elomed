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
        <h1><i class="bi bi-clock-history text-primary"></i> Histórico de Visitas</h1>
        <p>Acompanhe os atendimentos concluídos e os diagnósticos que você registrou.</p>
    </header>

    <div class="table-container">
        <h2><i class="bi bi-clipboard2-pulse text-primary"></i> Visitas Concluídas e Diagnósticos</h2>
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
                    <th>Diagnóstico</th>
                    <th class="text-end">Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visitas_concluidas as $visita):
                    $dateFormatted = date('d/m/Y', strtotime($visita['data_agendamento']));
                    $timeRange = substr($visita['horario_inicio'], 0, 5) . ' - ' . substr($visita['horario_fim'], 0, 5);
                    $diagText = trim($visita['diagnostico_final'] ?? '');
                    $condutaText = trim($visita['conduta_medica'] ?? '');
                    $hasRecord = ($diagText !== '' || $condutaText !== '');
                ?>
                <tr>
                    <td><?= e($dateFormatted) ?></td>
                    <td><?= e($timeRange) ?></td>
                    <td class="fw-semibold"><?= e($visita['nome_paciente']) ?></td>
                    <td><?= e($visita['especialidade']) ?></td>
                    <td>
                        <?php if ($hasRecord): ?>
                            <span class="badge badge-success">Diagnóstico registrado</span>
                        <?php else: ?>
                            <span class="badge badge-default">Sem prontuário</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" type="button"
                                data-bs-toggle="collapse" data-bs-target="#detalhe-<?= (int) $visita['id'] ?>"
                                aria-expanded="false" aria-controls="detalhe-<?= (int) $visita['id'] ?>">
                            <i class="bi bi-eye me-1"></i>Ver
                        </button>
                    </td>
                </tr>
                <tr class="collapse" id="detalhe-<?= (int) $visita['id'] ?>">
                    <td colspan="6">
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                                <strong class="text-primary"><i class="bi bi-person-vcard me-1"></i><?= e($visita['nome_paciente']) ?></strong>
                                <small class="text-muted"><?= e($dateFormatted) ?> &mdash; <?= e($timeRange) ?></small>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <span class="fw-semibold d-block mb-1"><i class="bi bi-clipboard2-check me-1"></i>Diagnóstico Final</span>
                                    <div class="p-2 bg-white rounded-2 border"><?= $diagText !== '' ? nl2br(e($diagText)) : '<em class="text-muted">Não informado</em>' ?></div>
                                </div>
                                <div class="col-md-6">
                                    <span class="fw-semibold d-block mb-1"><i class="bi bi-prescription2 me-1"></i>Conduta Médica</span>
                                    <div class="p-2 bg-white rounded-2 border"><?= $condutaText !== '' ? nl2br(e($condutaText)) : '<em class="text-muted">Não informada</em>' ?></div>
                                </div>
                                <?php if (!empty($visita['queixa_principal'])): ?>
                                <div class="col-md-6">
                                    <span class="fw-semibold d-block mb-1"><i class="bi bi-chat-left-quote me-1"></i>Queixa Principal</span>
                                    <div class="p-2 bg-white rounded-2 border"><?= nl2br(e($visita['queixa_principal'])) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($visita['historico_medico'])): ?>
                                <div class="col-md-6">
                                    <span class="fw-semibold d-block mb-1"><i class="bi bi-journal-medical me-1"></i>Histórico</span>
                                    <div class="p-2 bg-white rounded-2 border"><?= nl2br(e($visita['historico_medico'])) ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
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
