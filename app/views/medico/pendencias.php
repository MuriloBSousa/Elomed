<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendências - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<?php
    $rotuloProfissional = 'rotulo_profissional';
    $badgeRisco = 'badge_risco';
?>

    <div class="main-container">
        <header class="page-header">
            <h1>Pendências</h1>
            <p>Aprove ou recuse agendamentos, valide as triagens encaminhadas pela equipe e atribua as consultas à equipe de enfermagem.</p>
        </header>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="table-container h-100">
                    <h2><i class="bi bi-hourglass-split text-warning"></i> Agendamentos Pendentes</h2>

                    <?php if (!empty($aprovar_agendamento) && is_array($aprovar_agendamento)): ?>
                        <?php foreach ($aprovar_agendamento as $appointment): ?>
                            <div class="py-3" style="border-bottom:1px dashed #e2e8f0;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold"><?= e($appointment['nome_paciente']) ?></div>
                                        <div class="small text-muted">
                                            <?= date('d/m/Y', strtotime($appointment['data_agendamento'])) ?>
                                            às <?= e(substr($appointment['horario_inicio'], 0, 5)) ?> -
                                            <?= e(substr($appointment['horario_fim'], 0, 5)) ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="small text-muted">Risco (IA)</div>
                                        <?php if (!empty($appointment['classificacao_ia'])): ?>
                                            <span class="badge <?= $badgeRisco($appointment['classificacao_ia']) ?>">
                                                <?= e($appointment['classificacao_ia']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-info">análise pendente</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($appointment['justificativa_classificacao_ia'])): ?>
                                    <div class="small text-muted mt-2">
                                        <i class="bi bi-lightbulb me-1"></i>
                                        <?= e($appointment['justificativa_classificacao_ia']) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($appointment['profissional_recomendado_ia'])): ?>
                                    <div class="small mt-1">
                                        <i class="bi bi-people me-1 text-primary"></i>
                                        <strong><?= $rotuloProfissional($appointment['profissional_recomendado_ia']) ?></strong> recomendado(a) pela IA
                                        <?php if (!empty($appointment['justificativa_recomendacao_ia'])): ?>
                                            <span class="text-muted">- <?= e($appointment['justificativa_recomendacao_ia']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($appointment['nome_solicitante'])): ?>
                                    <div class="small mt-1">
                                        <i class="bi bi-clipboard2-pulse me-1 text-muted"></i>
                                        Triagem de <strong><?= e($appointment['nome_solicitante']) ?></strong>
                                        <?php if (!empty($appointment['triagem_classificacao'])): ?>
                                            - <span class="badge <?= $badgeRisco($appointment['triagem_classificacao']) ?>"><?= e($appointment['triagem_classificacao']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="<?= BASE_URL ?>/medico/aceitar/<?= (int)$appointment['id'] ?>"
                                      class="mt-3 bg-light p-3 rounded" style="border:1px solid #e2e8f0;">
                                    <?= CSRF::getField() ?>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small mb-1">Classificação final (override)</label>
                                            <select name="classificacao" class="form-select form-select-sm">
                                                <option value="">Manter a IA (<?= e($appointment['classificacao_ia'] ?? 'verde') ?>)</option>
                                                <option value="verde">Verde - comum</option>
                                                <option value="amarelo">Amarelo - prioridade</option>
                                                <option value="vermelho">Vermelho - urgente</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small mb-1">Atribuir a consulta a</label>
                                            <select name="fk_profissional_novo" class="form-select form-select-sm js-seletor-profissional"
                                                    data-titulo="Atribuir a consulta a">
                                                <option value="0"
                                                        data-nome="Realizar com o médico solicitante"
                                                        data-curto="Médico solicitante"
                                                        data-descricao="">Realizar com o médico solicitante</option>
                                                <?php if (!empty($equipe_encaminhamento) && is_array($equipe_encaminhamento)): ?>
                                                    <?php foreach ($equipe_encaminhamento as $membro):
                                                        $descMembro = trim(implode(' · ', array_filter([
                                                            $rotuloProfissional($membro['perfil']),
                                                            $membro['especialidade'] ?? '',
                                                            !empty($membro['tempo_experiencia_anos']) ? (int) $membro['tempo_experiencia_anos'] . ' ano(s)' : '',
                                                            $membro['numero_registro'] ?? ''
                                                        ])));
                                                        $fotoMembro = !empty($membro['foto']) ? BASE_URL . '/' . $membro['foto'] : '';
                                                    ?>
                                                        <option value="<?= (int)$membro['id_profissional'] ?>"
                                                                data-nome="<?= e($membro['nome_profissional']) ?>"
                                                                data-curto="<?= e($membro['nome_profissional']) ?>"
                                                                data-descricao="<?= e($descMembro) ?>"
                                                                data-foto="<?= e($fotoMembro) ?>">
                                                            <?= e($membro['nome_profissional']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <input type="text" name="justificativa_encaminhamento" class="form-control form-control-sm"
                                                   placeholder="Justificativa do encaminhamento (opcional)" maxlength="500">
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-sm btn-outline-success w-100">
                                                <i class="bi bi-check-lg me-1"></i> Aceitar
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <form method="POST" action="<?= BASE_URL ?>/medico/rejeitar/<?= (int)$appointment['id'] ?>" class="mt-2"
                                      onsubmit="var j=prompt('Motivo da recusa:');if(j===null){return false;}this.justificativa.value=j;return true;">
                                    <?= CSRF::getField() ?>
                                    <input type="hidden" name="justificativa" value="">
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                        <i class="bi bi-x-lg me-1"></i> Rejeitar
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="sem-dados"><i class="bi bi-calendar-check"></i> Nenhum agendamento pendente</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="table-container h-100">
                    <h2><i class="bi bi-clipboard2-pulse text-primary"></i> Triagens Pendentes</h2>

                    <?php if (!empty($aprovar_triagem) && is_array($aprovar_triagem)): ?>
                        <?php foreach ($aprovar_triagem as $triage): ?>
                            <div class="py-3" style="border-bottom:1px dashed #e2e8f0;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold"><?= e($triage['nome_solicitante']) ?></div>
                                        <div class="small text-muted">
                                            Enviada em <?= date('d/m/Y H:i', strtotime($triage['criado_em'])) ?>
                                        </div>
                                        <div class="mt-2">
                                            <?php if (!empty($triage['classificacao'])): ?>
                                                <span class="badge <?= $badgeRisco($triage['classificacao']) ?>">
                                                    <?= e($triage['classificacao']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-info">sem classificação</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($triage['justificativa_encaminhamento'])): ?>
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-arrow-right-circle me-1"></i>
                                                Encaminhada pela equipe: <?= e($triage['justificativa_encaminhamento']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($pode_aprovar_triagem)): ?>
                                        <div class="d-flex gap-2">
                                            <form method="POST" action="<?= BASE_URL ?>/medico/aprovar_triagem/<?= (int)$triage['id'] ?>"
                                                  onsubmit="return confirm('Aprovar esta triagem?');">
                                                <?= CSRF::getField() ?>
                                                <select name="classificacao" class="form-select form-select-sm mb-1">
                                                    <option value="">Manter</option>
                                                    <option value="verde">Verde</option>
                                                    <option value="amarelo">Amarelo</option>
                                                    <option value="vermelho">Vermelho</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-success w-100">
                                                    <i class="bi bi-check-lg me-1"></i> Aprovar
                                                </button>
                                            </form>
                                            <form method="POST" action="<?= BASE_URL ?>/medico/rejeitar_triagem/<?= (int)$triage['id'] ?>"
                                                  onsubmit="var j=prompt('Motivo da recusa:');if(j===null){return false;}this.justificativa.value=j;return true;">
                                                <?= CSRF::getField() ?>
                                                <input type="hidden" name="justificativa" value="">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="sem-dados"><i class="bi bi-check2-all"></i> Nenhuma triagem pendente</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>