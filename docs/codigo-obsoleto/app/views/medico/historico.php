<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Diagnósticos - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1><i class="bi bi-clock-history text-primary"></i> Histórico de Diagnósticos</h1>
        <p>Registros clínicos concluídos que você emitiu.</p>
    </header>

    <div class="table-container">
        <h2><i class="bi bi-clipboard2-pulse text-primary"></i> Diagnósticos Registrados</h2>
        <?php if (empty($diagnosticos)): ?>
            <p class="sem-dados"><i class="bi bi-inbox"></i> Nenhum diagnóstico encontrado.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Paciente</th>
                        <th>Diagnóstico Final</th>
                        <th class="text-end">Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diagnosticos as $diag): ?>
                        <?php $dateFormatted = date('d/m/Y H:i', strtotime($diag['criado_em'] ?? 'now')); ?>
                        <tr>
                            <td><?= e($dateFormatted) ?></td>
                            <td class="fw-semibold"><?= e($diag['paciente_nome'] ?? '-') ?></td>
                            <td><?= e(substr($diag['diagnostico_final'] ?? '', 0, 60)) ?><?= mb_strlen($diag['diagnostico_final'] ?? '') > 60 ? '...' : '' ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#diag-<?= (int) $diag['id'] ?>"
                                        aria-expanded="false" aria-controls="diag-<?= (int) $diag['id'] ?>">
                                    <i class="bi bi-eye me-1"></i>Ver
                                </button>
                            </td>
                        </tr>
                        <tr class="collapse" id="diag-<?= (int) $diag['id'] ?>">
                            <td colspan="4">
                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                                        <strong class="text-primary"><i class="bi bi-person-vcard me-1"></i><?= e($diag['paciente_nome'] ?? '-') ?></strong>
                                        <small class="text-muted"><?= e($dateFormatted) ?></small>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <span class="fw-semibold d-block mb-1"><i class="bi bi-chat-left-quote me-1"></i>Queixa Principal</span>
                                            <div class="p-2 bg-white rounded-2 border"><?= !empty($diag['queixa_principal']) ? nl2br(e($diag['queixa_principal'])) : '<em class="text-muted">Não informada</em>' ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <span class="fw-semibold d-block mb-1"><i class="bi bi-journal-medical me-1"></i>Sintomas</span>
                                            <div class="p-2 bg-white rounded-2 border"><?= !empty($diag['sintomas']) ? nl2br(e($diag['sintomas'])) : '<em class="text-muted">Não informados</em>' ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <span class="fw-semibold d-block mb-1"><i class="bi bi-clipboard2-check me-1"></i>Diagnóstico Final</span>
                                            <div class="p-2 bg-white rounded-2 border"><?= !empty($diag['diagnostico_final']) ? nl2br(e($diag['diagnostico_final'])) : '<em class="text-muted">Não informado</em>' ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <span class="fw-semibold d-block mb-1"><i class="bi bi-prescription2 me-1"></i>Conduta Médica</span>
                                            <div class="p-2 bg-white rounded-2 border"><?= !empty($diag['conduta_medica']) ? nl2br(e($diag['conduta_medica'])) : '<em class="text-muted">Não informada</em>' ?></div>
                                        </div>
                                        <?php if (!empty($diag['historico_medico'])): ?>
                                        <div class="col-md-12">
                                            <span class="fw-semibold d-block mb-1"><i class="bi bi-folder2-open me-1"></i>Histórico Clínico</span>
                                            <div class="p-2 bg-white rounded-2 border"><?= nl2br(e($diag['historico_medico'])) ?></div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>