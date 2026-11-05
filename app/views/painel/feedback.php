<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliações dos Pacientes - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<?php
    $estrelas = static function ($nota, $classe = 'fs-warning') {
        $html = '<span class="' . $classe . '">';
        for ($i = 1; $i <= 5; $i++) {
            $html .= $i <= (int) $nota
                ? '<i class="bi bi-star-fill"></i>'
                : '<i class="bi bi-star"></i>';
        }
        return $html . '</span>';
    };
    $corNota = static function ($nota) {
        $nota = (int) $nota;
        if ($nota >= 4) {
            return 'badge-success';
        }
        if ($nota === 3) {
            return 'badge-warning';
        }
        return 'badge-error';
    };
?>

<div class="main-container">
    <header class="page-header">
        <h1><i class="bi bi-star-half text-warning"></i> Avaliações dos Pacientes</h1>
        <p>Feedback registrado pelos pacientes após cada atendimento, de 0 a 5 estrelas.</p>
    </header>

    <div class="alert alert-secondary d-flex align-items-center py-2" style="font-size:.875rem;">
        <i class="bi bi-shield-lock me-2"></i>
        <span>
            Esta tela é restrita à administração. As avaliações não são exibidas ao profissional
            avaliado nem aos demais perfis.
        </span>
    </div>

    <div class="table-container">
        <h2><i class="bi bi-bar-chart-line text-primary"></i> Visão geral</h2>

        <?php
            $total = (int) ($indicadores['total'] ?? 0);
            $media = $indicadores['media'] ?? null;
        ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="p-3 rounded text-center" style="background:var(--bg-soft,#f8f9fa);">
                    <div class="h3 mb-0"><?= $total ?></div>
                    <div class="small text-muted">Avaliações</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="p-3 rounded text-center" style="background:var(--bg-soft,#f8f9fa);">
                    <div class="h3 mb-0"><?= $media !== null ? e($media) : '-' ?></div>
                    <div class="small text-muted">Média geral</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="p-3 rounded text-center" style="background:var(--bg-soft,#f8f9fa);">
                    <div class="h3 mb-0"><?= (int) ($indicadores['cinco_estrelas'] ?? 0) ?></div>
                    <div class="small text-muted">5 estrelas</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="p-3 rounded text-center" style="background:var(--bg-soft,#f8f9fa);">
                    <div class="h3 mb-0"><?= (int) (($indicadores['uma_estrela'] ?? 0) + ($indicadores['zero_estrelas'] ?? 0)) ?></div>
                    <div class="small text-muted">Críticas (0-1)</div>
                </div>
            </div>
        </div>

        <?php if ($total > 0): ?>
            <div class="mb-4">
                <h3 class="h6">Distribuição das notas</h3>
                <?php
                    $faixas = [
                        ['rotulo' => '5 estrelas', 'chave' => 'cinco_estrelas', 'cor' => 'bg-success'],
                        ['rotulo' => '4 estrelas', 'chave' => 'quatro_estrelas', 'cor' => 'bg-primary'],
                        ['rotulo' => '3 estrelas', 'chave' => 'tres_estrelas', 'cor' => 'bg-warning'],
                        ['rotulo' => '2 estrelas', 'chave' => 'duas_estrelas', 'cor' => 'bg-orange'],
                        ['rotulo' => '1 estrela',  'chave' => 'uma_estrela',   'cor' => 'bg-danger'],
                        ['rotulo' => '0 estrelas', 'chave' => 'zero_estrelas',  'cor' => 'bg-secondary'],
                    ];
                ?>
                <?php foreach ($faixas as $faixa): ?>
                    <?php
                        $qtd = (int) ($indicadores[$faixa['chave']] ?? 0);
                        $pct = $total > 0 ? round(($qtd / $total) * 100, 1) : 0;
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="small text-muted" style="min-width:78px;"><?= e($faixa['rotulo']) ?></span>
                        <div class="progress flex-grow-1" style="height:8px;">
                            <div class="progress-bar <?= $faixa['cor'] ?>" style="width:<?= $pct ?>%"
                                 role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <span class="small text-muted" style="min-width:52px;text-align:right;"><?= $qtd ?> (<?= $pct ?>%)</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2><i class="bi bi-person-badge text-primary"></i> Média por profissional</h2>
        <?php if (empty($porProfissional)): ?>
            <p class="sem-dados"><i class="bi bi-inbox"></i> Nenhuma avaliação registrada ainda.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Profissional</th>
                            <th>Especialidade</th>
                            <th>Média</th>
                            <th>Avaliações</th>
                            <th>5 estrelas</th>
                            <th>Críticas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($porProfissional as $linha): ?>
                            <tr>
                                <td><?= e($linha['nome_profissional']) ?></td>
                                <td class="text-muted"><?= e($linha['especialidade']) ?></td>
                                <td>
                                    <?= $estrelas(round($linha['media']), 'fs-warning') ?>
                                    <span class="small text-muted ms-1"><?= e($linha['media']) ?></span>
                                </td>
                                <td><?= (int) $linha['total'] ?></td>
                                <td><?= (int) $linha['cinco_estrelas'] ?></td>
                                <td>
                                    <?php if ((int) $linha['criticas'] > 0): ?>
                                        <span class="badge badge-error"><?= (int) $linha['criticas'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 class="mt-4"><i class="bi bi-chat-left-text text-primary"></i> Avaliações recentes</h2>
        <?php if (empty($feedbacks)): ?>
            <p class="sem-dados"><i class="bi bi-inbox"></i> Nenhum feedback registrado até o momento.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Quando</th>
                            <th>Profissional</th>
                            <th>Paciente</th>
                            <th>Nota</th>
                            <th>Comentário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedbacks as $fb): ?>
                            <tr>
                                <td class="text-nowrap">
                                    <?= date('d/m/Y H:i', strtotime($fb['criado_em'])) ?>
                                </td>
                                <td>
                                    <?= e($fb['nome_profissional']) ?>
                                    <div class="small text-muted"><?= e($fb['especialidade']) ?></div>
                                </td>
                                <td class="text-muted"><?= e($fb['nome_paciente']) ?></td>
                                <td>
                                    <span class="badge <?= $corNota($fb['nota']) ?>"><?= (int) $fb['nota'] ?>/5</span>
                                    <?= $estrelas($fb['nota']) ?>
                                </td>
                                <td>
                                    <?php if (!empty($fb['comentario'])): ?>
                                        <?= e($fb['comentario']) ?>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
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
