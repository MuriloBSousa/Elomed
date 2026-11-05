<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Histórico de Visitas</h1>
        <p>Visualize todas as suas consultas médicas anteriores e avalie o atendimento recebido.</p>
    </header>

    <?php
        $avaliaveis = $avaliaveis ?? [];
        $jaAvaliados = $jaAvaliados ?? [];
        $idsAvaliaveis = array_map('intval', array_column($avaliaveis, 'id_atendimento'));
        $idsAvaliados = [];
        foreach ($jaAvaliados as $av) {
            $idsAvaliados[(int) $av['id_atendimento']] = $av;
        }

        $estrelas = static function ($nota, $classe = 'fs-warning') {
            $html = '<span class="' . $classe . '">';
            for ($i = 1; $i <= 5; $i++) {
                $html .= $i <= (int) $nota
                    ? '<i class="bi bi-star-fill"></i>'
                    : '<i class="bi bi-star"></i>';
            }
            return $html . '</span>';
        };
    ?>

    <div class="table-container">
        <h2><i class="bi bi-clock-history text-primary"></i> Histórico</h2>
        <?php if (empty($visitas)): ?>
            <p class="sem-dados"><i class="bi bi-inbox"></i> Nenhuma visita concluída encontrada.</p>
        <?php else: ?>
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Profissional</th>
                    <th>Especialidade</th>
                    <th>Conduta</th>
                    <th>Sua avaliação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visitas as $visit):
                    $dateFormatted = date('d/m/Y', strtotime($visit['data_agendamento']));
                    $timeRange = substr($visit['horario_inicio'], 0, 5) . ' - ' . substr($visit['horario_fim'], 0, 5);
                    $condutaText = $visit['conduta_medica'] ?? 'Não informado';
                    $idAtendimento = (int) ($visit['id_atendimento'] ?? 0);
                    $avaliado = $idsAvaliados[$idAtendimento] ?? null;
                    $podeAvaliar = $idAtendimento > 0 && in_array($idAtendimento, $idsAvaliaveis, true);
                ?>
                <tr>
                    <td><?= e($dateFormatted) ?></td>
                    <td><?= e($timeRange) ?></td>
                    <td><?= e($visit['nome_profissional']) ?></td>
                    <td><?= e($visit['especialidade']) ?></td>
                    <td><?= e(substr($condutaText, 0, 50)) ?><?= strlen($condutaText) > 50 ? '...' : '' ?></td>
                    <td>
                        <?php if ($avaliado): ?>
                            <?= $estrelas($avaliado['nota'], 'fs-warning') ?>
                            <span class="small text-muted ms-1"><?= (int) $avaliado['nota'] ?>/5</span>
                            <?php if (!empty($avaliado['comentario'])): ?>
                                <div class="small text-muted fst-italic mt-1">"<?= e($avaliado['comentario']) ?>"</div>
                            <?php endif; ?>
                        <?php elseif ($podeAvaliar): ?>
                            <button type="button" class="btn btn-sm btn-outline-warning"
                                    data-bs-toggle="modal" data-bs-target="#avaliar<?= $idAtendimento ?>">
                                <i class="bi bi-star me-1"></i> Avaliar
                            </button>

                            <div class="modal fade" id="avaliar<?= $idAtendimento ?>" tabindex="-1"
                                 aria-labelledby="tituloAvaliar<?= $idAtendimento ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form method="POST"
                                              action="<?= BASE_URL ?>/paciente/avaliar_atendimento/<?= $idAtendimento ?>">
                                            <?= CSRF::getField() ?>
                                            <input type="hidden" name="nota" id="nota<?= $idAtendimento ?>" value="5">

                                            <div class="modal-header">
                                                <h5 class="modal-title" id="tituloAvaliar<?= $idAtendimento ?>">
                                                    Avaliar atendimento
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="mb-1">
                                                    <strong><?= e($visit['nome_profissional']) ?></strong>
                                                    <span class="text-muted">(<?= e($visit['especialidade']) ?>)</span>
                                                </p>
                                                <p class="small text-muted">
                                                    <?= e($dateFormatted) ?> às <?= e(substr($visit['horario_inicio'], 0, 5)) ?>
                                                </p>

                                                <label class="form-label small mb-1">Como foi o atendimento?</label>
                                                <div class="d-flex align-items-center gap-1 mb-2" data-nota="<?= $idAtendimento ?>">
                                                    <?php for ($n = 1; $n <= 5; $n++): ?>
                                                        <button type="button" class="btn btn-link p-0 fs-warning star-btn"
                                                                data-valor="<?= $n ?>" style="font-size:1.6rem;line-height:1;"
                                                                aria-label="<?= $n ?> estrelas">
                                                            <i class="bi bi-star-fill"></i>
                                                        </button>
                                                    <?php endfor; ?>
                                                    <span class="ms-2 small text-muted" id="rotulo<?= $idAtendimento ?>">5 estrelas</span>
                                                </div>

                                                <label class="form-label small mb-1" for="comentario<?= $idAtendimento ?>">
                                                    Comentário (opcional)
                                                </label>
                                                <textarea class="form-control form-control-sm" rows="3"
                                                          id="comentario<?= $idAtendimento ?>" name="comentario"
                                                          maxlength="500"
                                                          placeholder="Conte como foi a experiência..."></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                        data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-send me-1"></i> Enviar avaliação
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
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

<script>
(function () {
    document.querySelectorAll('[data-nota]').forEach(function (grupo) {
        var id = grupo.getAttribute('data-nota');
        var campo = document.getElementById('nota' + id);
        var rotulo = document.getElementById('rotulo' + id);
        var botoes = grupo.querySelectorAll('.star-btn');

        function pintar(valor) {
            botoes.forEach(function (botao) {
                var n = parseInt(botao.getAttribute('data-valor'), 10);
                var icone = botao.querySelector('i');
                icone.className = n <= valor ? 'bi bi-star-fill' : 'bi bi-star';
            });
            campo.value = valor;
            rotulo.textContent = valor + (valor === 1 ? ' estrela' : ' estrelas');
        }

        botoes.forEach(function (botao) {
            botao.addEventListener('click', function () {
                pintar(parseInt(botao.getAttribute('data-valor'), 10));
            });
        });

        pintar(5);
    });
})();
</script>
</body>
</html>
