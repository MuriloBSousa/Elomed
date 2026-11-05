<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Triagens Pendentes - Enfermagem - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

    <div class="main-container">
        <header class="page-header">
            <h1><i class="bi bi-hourglass-split text-warning"></i> Triagens Pendentes</h1>
            <p>Classifique e decida as triagens encaminhadas pela equipe de análise.</p>
        </header>

        <div class="table-container">
            <h2><i class="bi bi-clipboard2-pulse text-primary"></i> Solicitações</h2>
            <?php if (empty($triagens)): ?>
                <p class="sem-dados"><i class="bi bi-check2-all"></i> Nenhuma triagem pendente no momento.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Paciente</th>
                            <th>Enviada em</th>
                            <th>Visita agendada</th>
                            <th class="text-end" style="width: 300px;">Decisão</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($triagens as $tri): ?>
                        <tr>
                            <td>#<?= (int) $tri['id'] ?></td>
                            <td><?= e($tri['paciente_nome'] ?? '-') ?>
                                <?php if (!empty($tri['justificativa_encaminhamento'])): ?>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-arrow-right-circle me-1"></i>
                                        <?= e($tri['justificativa_encaminhamento']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('d/m/Y H:i', strtotime($tri['criado_em'] ?? 'now'))) ?></td>
                            <td>
                                <?php if (!empty($tri['id_agendamento']) && !empty($tri['data_agendamento'])): ?>
                                    <div class="small">
                                        <i class="bi bi-calendar2-check me-1"></i>
                                        <strong><?= e(date('d/m/Y', strtotime($tri['data_agendamento']))) ?></strong>
                                        &middot; <?= e(substr((string) $tri['horario_inicio'], 0, 5)) ?>
                                        &ndash; <?= e(substr((string) $tri['horario_fim'], 0, 5)) ?>
                                    </div>
                                    <div class="small text-muted">Visita agendada pela equipe de enfermagem</div>
                                <?php else: ?>
                                    <span class="small text-muted">Sem visita agendada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2 align-items-center justify-content-end flex-wrap">
                                    <form method="POST" action="<?= BASE_URL ?>/enfermeiro/aprovar_triagem/<?= (int) $tri['id'] ?>"
                                          class="d-flex gap-2 align-items-center"
                                          onsubmit="return confirm('Aprovar esta triagem?');">
                                        <?= CSRF::getField() ?>
                                        <select name="classificacao" class="form-select form-select-sm" style="width: 120px;" required>
                                            <option value="">Classificar</option>
                                            <option value="verde">Verde - comum</option>
                                            <option value="amarelo">Amarelo - prioridade</option>
                                            <option value="vermelho">Vermelho - urgente</option>
                                        </select>
                                        <button type="submit" class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-check-lg me-1"></i> Aprovar
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= BASE_URL ?>/enfermeiro/recusar_triagem/<?= (int) $tri['id'] ?>"
                                          onsubmit="var j=prompt('Motivo da recusa (opcional):');if(j===null){return false;}this.justificativa.value=j;return true;">
                                        <?= CSRF::getField() ?>
                                        <input type="hidden" name="justificativa" value="">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Recusar">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
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