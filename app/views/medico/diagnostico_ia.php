<?php
Session::start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análise da IA - Diagnóstico - EloMed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/Assets/css/app.css">
</head>
<body>
    <div class="container py-4">
        <div class="table-container">
            <div class="text-center mb-4">
                <span class="badge badge-primary text-uppercase mb-2 px-3 py-2"><i class="bi bi-cpu me-1"></i> Módulo de IA Ativo</span>
                <h1 class="h4 fw-bold">Análise Diagnóstica Auxiliada</h1>
                <p class="text-muted mb-0">Validação de sintomas e recomendações farmacológicas</p>
            </div>

            <div class="bg-light border-start border-4 border-secondary-subtle rounded p-3 mb-4 small">
                <div class="fw-semibold">Resumo Clínico Coletado:</div>

                <?php if (!empty($protocolo_id) && !empty($respostas_protocolo)): ?>
                    <p class="mb-1 mt-2"><strong>Protocolo Selecionado:</strong> Protocolo ID <?= (int)$protocolo_id ?></p>
                    <p class="mb-1"><strong>Respostas do Protocolo:</strong></p>
                    <ul class="ms-4">
                        <?php
                        $questionMap = [];
                        if (!empty($perguntas_protocolo)) {
                            foreach ($perguntas_protocolo as $pergunta) {
                                $questionMap[$pergunta['id']] = $pergunta['pergunta'];
                            }
                        }
                        foreach ($respostas_protocolo as $pergunta_id => $resposta):
                            $questionText = isset($questionMap[$pergunta_id]) ? $questionMap[$pergunta_id] : "Pergunta ID $pergunta_id";
                        ?>
                            <li><strong><?= e($questionText) ?>:</strong> <?= e($resposta) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="mb-1 mt-2"><strong>Sintomas relatados:</strong> <?= e($queixa ?? 'Não informado') ?></p>
                    <p class="mb-1"><strong>Tempo de evolução:</strong> <?= e($tempo ?? 'Não informado') ?></p>
                    <p class="mb-1"><strong>Alergias:</strong> <?= e($alergia ?? 'Nenhuma') ?></p>
                    <p class="mb-1"><strong>Histórico:</strong> <?= e($historico ?? 'Não informado') ?></p>
                <?php endif; ?>

                <p class="mb-0 mt-2"><strong>Sinais Vitais:</strong>
                    PA: <?= e($pa ?? 'Não medido') ?> |
                    FC: <?= e($fc ?? 'Não medido') ?> bpm |
                    Temp: <?= e($temperatura ?? 'Não medida') ?> |
                    Glicemia: <?= e($glicemia ?? 'Não medida') ?> mg/dL
                </p>
            </div>

            <div class="rounded p-4 mb-4" style="background:#f5f3ff; border:1px solid #ddd6fe;">
                <div class="alert alert-warning small d-flex" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                    <span><strong>Análise PRELIMINAR gerada por IA - aguarda a validação do médico.</strong>
                    Esta resposta não é diagnóstico final e deve ser revisada.
                    Modelo: <?= e($modelo_ia ?? 'groq') ?> | Gerada às: <?= e($horario_ia ?? '-') ?></span>
                </div>

                <h2 class="section-title"><i class="bi bi-stars"></i> Insights e Sugestões Preditivas da IA</h2>

                <div class="bg-white rounded border p-3 mb-3 small">
                    <strong><i class="bi bi-graph-up-arrow text-primary me-1"></i> Correlação de Diagnósticos Prováveis:</strong>
                    <p class="mt-2 mb-0 text-muted">
                        <?php
                        $correlation = $insights['correlacao'] ?? 'Análise não disponível.';
                        if (is_array($correlation)) {
                            $correlation = print_r($correlation, true);
                        }
                        echo nl2br(e($correlation));
                        ?>
                    </p>
                </div>

                <div class="bg-white rounded border border-start border-4 border-warning p-3 mb-3 small">
                    <strong><i class="bi bi-shield-exclamation text-warning me-1"></i> Alerta de Segurança Terapêutica:</strong>
                    <p class="mt-2 mb-0 text-muted">
                        <?php
                        $alert = $insights['alerta'] ?? 'Nenhum alerta gerado.';
                        if (is_array($alert)) {
                            $alert = print_r($alert, true);
                        }
                        echo nl2br(e($alert));
                        ?>
                    </p>
                </div>

                <div class="bg-white rounded border border-start border-4 border-success p-3 mb-3 small">
                    <strong><i class="bi bi-lightbulb text-success me-1"></i> Recomendações de Práticas de Suporte:</strong>
                    <p class="mt-2 mb-0 text-muted">
                        <?php
                        $recommendations = $insights['recomendacoes'] ?? 'Recomendações não disponíveis.';
                        if (is_array($recommendations)) {
                            $recommendations = print_r($recommendations, true);
                        }
                        echo nl2br(e($recommendations));
                        ?>
                    </p>
                </div>
            </div>

            <form action="<?= BASE_URL ?>/medico/salvar_diagnostico_final" method="POST">
                <?= CSRF::getField() ?>
                <input type="hidden" name="id_agendamento" value="<?= (int)($id_agendamento ?? 0) ?>">

                <h2 class="section-title"><i class="bi bi-pencil-square"></i> Conclusão e Ajuste do Médico</h2>
                <div class="mb-3">
                    <label for="diagnostico_final" class="form-label">Diagnóstico Clínico Definitivo (Após validação da IA)</label>
                    <textarea id="diagnostico_final" name="diagnostico_final" rows="3" class="form-control" required placeholder="Digite o diagnóstico conclusivo e o CID se aplicável..."></textarea>
                </div>
                <div class="mb-3">
                    <label for="ajuste_conduta" class="form-label">Ajustes Finais na Receita ou Conduta Médica</label>
                    <textarea id="ajuste_conduta" name="ajuste_conduta" rows="3" class="form-control" placeholder="Caso tenha alterado dosagens ou adicionado exames complementares após os insights da IA, registre aqui..."></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Voltar para Anamnese
                    </a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="bi bi-save me-1"></i> Finalizar e Salvar Prontuário
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
