<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Triagens - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

    <div class="main-container">
        <header class="page-header">
            <h1>Minhas Triagens</h1>
            <p>Solicite novos atendimentos e acompanhe a análise da nossa equipe médica.</p>
        </header>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="table-container h-100">
                    <h2><i class="bi bi-plus-circle-dotted text-primary"></i> Nova Solicitação</h2>

                    <div class="alert alert-secondary py-2 small mb-3">
                        <strong>O que é a Triagem?</strong>
                        É a avaliação clínica inicial. Você descreve seus sintomas e a nossa equipe de enfermagem / médica
                        classifica o grau de risco (pulseira vermelha, amarela ou verde) e decide a melhor conduta.
                        Quando a triagem é encaminhada, o técnico de enfermagem agenda a visita na data e no horário
                        que ele definir — você acompanha isso na coluna <em>Visita</em>.
                        Se preferir escolher o profissional e o horário, use a opção <em>Solicitar Agendamento</em>.
                    </div>

                    <form action="<?= BASE_URL ?>/paciente/processar_triagem" method="POST">
                        <?= CSRF::getField() ?>

                        <div class="mb-3">
                            <label for="nome_solicitante" class="form-label">Nome do Solicitante</label>
                            <input type="text" id="nome_solicitante" name="nome_solicitante"
                                   class="form-control"
                                   placeholder="Ex: Richard ou Nome de um Dependente" required>
                        </div>

                        <div class="mb-3">
                            <label for="queixa_principal" class="form-label">Queixa Principal *</label>
                            <input type="text" id="queixa_principal" name="queixa_principal"
                                   class="form-control"
                                   placeholder="Ex: Dor de cabeça persistente" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao_sintomas" class="form-label">Descrição dos Sintomas</label>
                            <textarea id="descricao_sintomas" name="descricao_sintomas" class="form-control" rows="3"
                                      placeholder="Descreva detalhadamente: quando começou, intensidade, se há outros sintomas..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="data_inicio_sintomas" class="form-label">Data de Início dos Sintomas</label>
                            <input type="date" id="data_inicio_sintomas" name="data_inicio_sintomas" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send me-1"></i> Enviar para Análise
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="table-container">
                    <h2><i class="bi bi-clock-history text-primary"></i> Histórico de Solicitações</h2>
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Protocolo</th>
                                <th>Solicitante</th>
                                <th>Risco</th>
                                <th>Data de Envio</th>
                                <th>Visita</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($triagens)): ?>
                                <tr>
                                    <td colspan="6" class="sem-dados"><i class="bi bi-inbox"></i> Nenhuma triagem encontrada.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($triagens as $triagem):
                                    $dateFormatted = date('d/m/Y', strtotime($triagem['data_criacao']));
                                    $statusValue = $triagem['status'] ?? 'pendente';
                                    $badgeClass = $statusValue;
                                ?>
                                <tr>
                                    <td>#<?= (int)$triagem['id'] ?></td>
                                    <td><?= e($triagem['nome_solicitante']) ?></td>
                                    <td>
                                        <?php if (!empty($triagem['classificacao'])): ?>
                                            <span class="badge badge-<?=
                                                $triagem['classificacao'] === 'vermelho' ? 'error' :
                                                ($triagem['classificacao'] === 'amarelo' ? 'warning' : 'success') ?>">
                                                <?= e($triagem['classificacao']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($dateFormatted) ?></td>
                                    <td>
                                        <?php if (!empty($triagem['id_agendamento']) && !empty($triagem['data_visita'])): ?>
                                            <strong><?= e(date('d/m/Y', strtotime($triagem['data_visita']))) ?></strong>
                                            <div class="small text-muted">
                                                <?= e(substr((string) $triagem['horario_inicio'], 0, 5)) ?>
                                                &ndash;
                                                <?= e(substr((string) $triagem['horario_fim'], 0, 5)) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">A definir pela equipe</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="status-table <?= e($badgeClass) ?>"><?= ucfirst(e($statusValue)) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
