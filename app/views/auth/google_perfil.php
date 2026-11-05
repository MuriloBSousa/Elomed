<?php
Session::start();
$pendente      = $pendente ?? Session::get('google_pendente');
$perfilEscolhido = $perfilEscolhido ?? '';

$perfis = [
    'medico'             => ['icon' => 'bi-person-badge',          'nome' => 'Médico'],
    'enfermeiro'         => ['icon' => 'bi-bandaid',               'nome' => 'Enfermeiro'],
    'tecnico_enfermagem' => ['icon' => 'bi-heart-pulse',           'nome' => 'Técnico de Enfermagem'],
    'paciente'           => ['icon' => 'bi-person-heart',          'nome' => 'Paciente'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EloMed - Escolher Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/Assets/css/app.css">
</head>
<body class="auth-page">
    <div class="auth-shell">

        <aside class="brand-panel" aria-hidden="true">
            <div class="brand-content">
                <span class="brand-icon mx-auto"><i class="bi bi-person-badge"></i></span>
                <span class="brand-wordmark mx-auto"><i class="bi bi-activity me-1"></i>EloMed<span class="dot"></span></span>
                <h1>Quase lá!</h1>
                <p>Conte para a EloMed como você quer se cadastrar e qual será sua função na plataforma.</p>
            </div>
            <div class="brand-footer">Cuidado e atendimento em casa &bull; EloMed</div>
        </aside>

        <section class="forms-stage">
            <div class="forms-track">
                <form class="auth-form signin-form active" action="<?= BASE_URL ?>/login/google_cadastrar" method="POST" novalidate>
                    <?= CSRF::getField() ?>

                    <div class="auth-form-head">
                        <h1>Escolha seu perfil</h1>
                        <p>Você está entrando com<br><strong><?= e($pendente['nome'] ?? '') ?></strong> <span class="text-muted">(<?= e($pendente['email'] ?? '') ?>)</span></p>
                    </div>

                    <div class="perfil-opcoes mb-4">
                        <?php foreach ($perfis as $slug => $p): ?>
                            <label class="perfil-opcao">
                                <input type="radio" name="perfil" value="<?= e($slug) ?>"
                                       <?= ($perfilEscolhido === $slug || ($perfilEscolhido === '' && $slug === 'paciente')) ? 'checked' : '' ?>>
                                <span class="perfil-card">
                                    <i class="bi <?= $p['icon'] ?>"></i>
                                    <strong><?= e($p['nome']) ?></strong>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-submit">
                        <i class="bi bi-check-lg me-1"></i> Criar conta
                    </button>

                    <p class="auth-help">
                        <a href="<?= BASE_URL ?>/login/index" class="link-recuperar">
                            <i class="bi bi-box-arrow-in-left me-1"></i> Voltar ao login
                        </a>
                    </p>
                </form>
            </div>
        </section>
    </div>
    <script src="<?= BASE_URL ?>/Assets/js/toasts.js"></script>
    <?php require_once __DIR__ . '/../../helpers/flash_toasts.php'; flash_toasts(); ?>
</body>
</html>