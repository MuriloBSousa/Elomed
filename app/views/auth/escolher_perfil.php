<?php
Session::start();
$usuarioNome  = Session::get('usuario_nome', 'Usuário');
$escolhido    = $selecionado ?? '';

$icones = [
    'medico'             => 'bi-person-badge',
    'enfermeiro'         => 'bi-bandaid',
    'tecnico_enfermagem' => 'bi-heart-pulse',
    'paciente'           => 'bi-person-heart',
    'admin'              => 'bi-shield-lock',
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
                <span class="brand-icon mx-auto"><i class="bi bi-person-check"></i></span>
                <span class="brand-wordmark mx-auto"><i class="bi bi-activity me-1"></i>EloMed<span class="dot"></span></span>
                <h1>Identifique sua função</h1>
                <p>Olá, <strong><?= e($usuarioNome) ?></strong>. Escolha o perfil que deseja usar neste acesso. O perfil de administrador é reservado e não aparece nesta lista.</p>
            </div>
            <div class="brand-footer">Cuidado e atendimento em casa &bull; EloMed</div>
        </aside>

        <section class="forms-stage">
            <div class="forms-track">
                <form class="auth-form signin-form active" action="<?= BASE_URL ?>/login/escolher_perfil" method="POST" novalidate>
                    <?= CSRF::getField() ?>

                    <div class="auth-form-head">
                        <h1>Escolha seu perfil</h1>
                        <p>Selecione como você quer entrar no sistema.</p>
                    </div>

                    <?php if (empty($perfisDisponiveis)): ?>
                        <div class="alert alert-warning d-flex align-items-center py-2 small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Nenhum perfil de acesso disponível para esta conta. Contate o administrador.
                        </div>
                    <?php endif; ?>

                    <div class="perfil-opcoes mb-4">
                        <?php foreach ($perfisDisponiveis as $perfil): ?>
                            <label class="perfil-opcao">
                                <input type="radio" name="perfil" value="<?= e($perfil['slug']) ?>"
                                       <?= ($escolhido === $perfil['slug'] || ($escolhido === '' && empty($perfisDisponiveis[1]))) ? 'checked' : '' ?>>
                                <span class="perfil-card">
                                    <i class="bi <?= $icones[$perfil['slug']] ?? 'bi-person' ?>"></i>
                                    <strong><?= e($perfil['nome']) ?></strong>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-submit" <?= empty($perfisDisponiveis) ? 'disabled' : '' ?>>
                        <i class="bi bi-box-arrow-in-right me-1"></i> Entrar neste perfil
                    </button>

                    <p class="auth-help">
                        <a href="<?= BASE_URL ?>/login/sair" class="link-recuperar">
                            <i class="bi bi-box-arrow-right me-1"></i> Não sou eu · Sair
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