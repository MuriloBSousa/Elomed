<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Triagens para Análise - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<?php
    $rotuloProfissional = 'rotulo_profissional';
    $badgeRisco = 'badge_risco';
    $rotuloClassificacao = 'rotulo_classificacao';
    $rotuloPerfil = 'rotulo_perfil_triagem';
?>

    <div class="main-container">
        <header class="page-header">
            <h1><i class="bi bi-clipboard2-pulse text-primary"></i> Triagens para Análise</h1>
            <p>Cada solicitação chega aqui já com uma avaliação prévia da IA. Revise, confirme ou corrija a classificação e o destino — a decisão final do encaminhamento é sua. O encaminhamento gera o agendamento da visita: a data e o horário são definidos por você, não pelo paciente.</p>
        </header>

        <div class="table-container">
            <h2><i class="bi bi-hourglass-split text-warning"></i> Solicitações Aguardando Análise</h2>
            <?php if (empty($triagens)): ?>
                <p class="sem-dados"><i class="bi bi-check2-all"></i> Nenhuma triagem pendente de análise no momento.</p>
            <?php else: ?>
                <?php foreach ($triagens as $tri) : ?>
                    <?php
                        $idTri = (int) $tri['id'];
                        $ctx = $porTriagem[$idTri] ?? ['profissionais' => [], 'sugerido' => null];
                        $lista = $ctx['profissionais'];
                        $sugerido = $ctx['sugerido'];
                        $idSugerido = $sugerido ? (int) $sugerido['id_profissional'] : 0;
                        $iaClassificacao = $tri['ia_classificacao'] ?? null;
                        $iaPerfil = $tri['ia_perfil_recomendado'] ?? null;
                    ?>
                    <div class="py-3" style="border-bottom:1px dashed #e2e8f0;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold">#<?= (int) $tri['id'] ?> · <?= e($tri['nome_solicitante']) ?>
                                    <span class="small text-muted">(<?= e($tri['nome_paciente'] ?? '-') ?>)</span>
                                </div>
                                <div class="small text-muted">
                                    Enviada em <?= date('d/m/Y H:i', strtotime($tri['criado_em'])) ?>
                                    <?php if (!empty($tri['data_inicio_sintomas'])): ?>
                                        · Início dos sintomas: <?= date('d/m/Y', strtotime($tri['data_inicio_sintomas'])) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2">
                                    <strong>Queixa:</strong> <?= e($tri['queixa_principal'] ?? '-') ?>
                                </div>
                                <?php if (!empty($tri['descricao_sintomas'])): ?>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-chat-left-text me-1"></i>
                                        <?= e($tri['descricao_sintomas']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted">Risco solicitado</div>
                                <?php if (!empty($tri['classificacao'])): ?>
                                    <span class="badge <?= $badgeRisco($tri['classificacao']) ?>"><?= e($tri['classificacao']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-info">sem classificação</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>/tecnicoenfermagem/encaminhar/<?= $idTri ?>"
                              data-visita-triagem
                              class="mt-3 bg-light p-3 rounded" style="border:1px solid #e2e8f0;">
                            <?= CSRF::getField() ?>

                            <?php if ($iaClassificacao): ?>
                                <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.875rem;">
                                    <div class="fw-semibold mb-1">
                                        <i class="bi bi-stars me-1"></i>
                                        Avaliação prévia da IA
                                        <?php if (!empty($tri['ia_modelo'])): ?>
                                            <span class="badge badge-info ms-1"><?= e($tri['ia_modelo']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-1">
                                        Risco sugerido:
                                        <span class="badge <?= $badgeRisco($iaClassificacao) ?>"><?= e($iaClassificacao) ?></span>
                                        <?php if (!empty($tri['ia_justificativa_classificacao'])): ?>
                                            <span class="text-muted"><?= e($tri['ia_justificativa_classificacao']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($iaPerfil): ?>
                                        <div class="mb-1">
                                            Perfil sugerido: <strong><?= e($rotuloPerfil($iaPerfil)) ?></strong>
                                            <?php if (!empty($tri['ia_justificativa_perfil'])): ?>
                                                <span class="text-muted"><?= e($tri['ia_justificativa_perfil']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($sugerido): ?>
                                        <div class="mb-0">
                                            Profissional sugerido: <strong><?= e($sugerido['nome_profissional']) ?></strong>
                                            <?php if (!empty($sugerido['especialidade'])): ?>
                                                <span class="text-muted">- <?= e($sugerido['especialidade']) ?></span>
                                            <?php endif; ?>
                                            <?php if (isset($sugerido['tempo_experiencia_anos'])): ?>
                                                <span class="text-muted">(<?= (int) $sugerido['tempo_experiencia_anos'] ?> ano(s) de experiência)</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-1 fst-italic text-muted" style="font-size:.8rem;">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Sugestão automática. A decisão final e a responsabilidade são do técnico de enfermagem.
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary py-2 px-3 mb-3" style="font-size:.875rem;">
                                    <i class="bi bi-dash-circle me-1"></i>
                                    Sem avaliação prévia da IA para esta solicitação. Classifique manualmente.
                                </div>
                            <?php endif; ?>

                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Classificação de risco (análise)</label>
                                    <select name="classificacao" class="form-select form-select-sm" required>
                                        <option value="">Classificar...</option>
                                        <?php foreach (['vermelho', 'amarelo', 'verde'] as $opcao): ?>
                                            <option value="<?= $opcao ?>"<?= ($iaClassificacao === $opcao) ? ' selected' : '' ?>>
                                                <?= e($rotuloClassificacao($opcao)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small mb-1">Encaminhar para</label>
                                    <select name="fk_profissional" class="form-select form-select-sm js-seletor-profissional" required
                                            data-titulo="Encaminhar para">
                                        <option value="">Selecione o profissional...</option>
                                        <?php foreach ($lista as $membro):
                                            $mId = (int) $membro['id_profissional'];
                                            $descMembro = trim(implode(' · ', array_filter([
                                                $rotuloProfissional($membro['perfil']),
                                                $membro['especialidade'] ?? '',
                                                (int) $membro['tempo_experiencia_anos'] . ' ano(s)',
                                                $membro['numero_registro'] ?? ''
                                            ])));
                                            if (!empty($membro['especialidade_correspondente'])) {
                                                $descMembro .= ' · especialidade relacionada';
                                            }
                                            $fotoMembro = !empty($membro['foto']) ? BASE_URL . '/' . $membro['foto'] : '';
                                        ?>
                                            <option value="<?= $mId ?>"<?= ($idSugerido === $mId) ? ' selected' : '' ?>
                                                    data-nome="<?= e($membro['nome_profissional']) ?>"
                                                    data-curto="<?= e($membro['nome_profissional']) ?>"
                                                    data-descricao="<?= e($descMembro) ?>"
                                                    data-foto="<?= e($fotoMembro) ?>">
                                                <?= e($membro['nome_profissional']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text" style="font-size:.75rem;">
                                        Ordenado por aderência à especialidade, tempo de experiência e carga do dia.
                                    </div>
                                </div>
                            </div>

                            <div class="border-top mt-3 pt-3" style="border-color:#e2e8f0 !important;">
                                <div class="small fw-semibold text-muted mb-2">
                                    <i class="bi bi-calendar2-check me-1"></i>
                                    Agendamento da visita — definido por você (técnico de enfermagem)
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1">Data da visita</label>
                                        <input type="date" name="data" class="form-control form-control-sm" required
                                               min="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1">Início</label>
                                        <input type="time" name="horario_inicio" class="form-control form-control-sm" required
                                               step="900">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1">Fim</label>
                                        <input type="time" name="horario_fim" class="form-control form-control-sm" required
                                               step="900">
                                    </div>
                                    <div class="col-12">
                                        <input type="text" name="justificativa_encaminhamento" class="form-control form-control-sm"
                                               placeholder="Justificativa do encaminhamento (opcional)" maxlength="500">
                                    </div>
                                    <div class="col-12">
                                        <div class="form-text" style="font-size:.75rem;">
                                            Ao encaminhar, a triagem vira um agendamento em nome do paciente e a
                                            visita entra na agenda do profissional, aguardando confirmação dele.
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-outline-success w-100 mt-1">
                                            <i class="bi bi-send me-1"></i> Encaminhar e agendar visita
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <form method="POST" action="<?= BASE_URL ?>/tecnicoenfermagem/recusar_triagem/<?= $idTri ?>" class="mt-2"
                              onsubmit="var j=prompt('Motivo da recusa:');if(j===null){return false;}this.justificativa.value=j;return true;">
                            <?= CSRF::getField() ?>
                            <input type="hidden" name="justificativa" value="">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                <i class="bi bi-x-lg me-1"></i> Recusar triagem
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    (function () {
        var PADRAO_MINUTOS = 60;
        document.querySelectorAll('form[data-visita-triagem]').forEach(function (form) {
            var inicio = form.querySelector('[name="horario_inicio"]');
            var fim = form.querySelector('[name="horario_fim"]');
            if (!inicio || !fim) {
                return;
            }
            var editado = false;
            fim.addEventListener('input', function () { editado = true; });
            inicio.addEventListener('change', function () {
                if (!inicio.value || (editado && fim.value)) {
                    return;
                }
                var partes = inicio.value.split(':');
                var total = parseInt(partes[0], 10) * 60 + parseInt(partes[1], 10) + PADRAO_MINUTOS;
                if (total >= 1440) {
                    return;
                }
                fim.value = String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
            });
        });
    })();
    </script>
</body>
</html>