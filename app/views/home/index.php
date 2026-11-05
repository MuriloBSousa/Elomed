<?php Session::start(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EloMed - Cuidado e Atendimento em Casa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/Assets/css/app.css">
<link rel="icon" href="<?= BASE_URL ?>/Assets/images/Logo_EloMed.png" type="image/png">

</head>
<body class="landing-page">

    <nav class="landing-nav">
        <a class="landing-brand" href="<?= BASE_URL ?>/home/index">
            <i class="bi bi-activity"></i> EloMed<span class="dot">.</span>
        </a>
        <div class="landing-nav-links">
            <a href="<?= BASE_URL ?>/login/index" class="btn btn-outline-light rounded-pill px-4">Entrar</a>
            <a href="<?= BASE_URL ?>/cadastro/index" class="btn btn-landing-cta rounded-pill px-4">Criar conta</a>
        </div>
    </nav>

    <main>
        <!-- HERO -->
        <section class="landing-hero">
            <div class="landing-hero-inner">
                <span class="landing-eyebrow"><i class="bi bi-stars me-1"></i> Cuidado que vai até você</span>
                <h1>Cuidado e atendimento<br>domiciliar em um só lugar.</h1>
                <p class="lead">A EloMed conecta pacientes, médicos, enfermeiros e técnicos de enfermagem em uma plataforma única — do agendamento à triagem, da consulta ao prontuário.</p>
                <div class="landing-hero-actions">
                    <a href="<?= BASE_URL ?>/login/index" class="btn btn-landing-cta btn-lg rounded-pill px-5">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Acessar o sistema
                    </a>
                    <a href="<?= BASE_URL ?>/cadastro/index" class="btn btn-outline-light btn-lg rounded-pill px-5">Criar minha conta</a>
                </div>
                <div class="landing-hero-meta">
                    <span><i class="bi bi-shield-check me-1"></i> Acesso seguro</span>
                    <span><i class="bi bi-people me-1"></i> Equipe multidisciplinar</span>
                    <span><i class="bi bi-clock-history me-1"></i> Atendimento agendado</span>
                </div>
            </div>
        </section>

        <!-- COMO FUNCIONA -->
        <section class="landing-section">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="landing-title">Como funciona</h2>
                    <p class="text-muted">Passos simples para todo o seu atendimento em casa.</p>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="landing-card h-100">
                            <span class="landing-card-icon"><i class="bi bi-person-plus"></i></span>
                            <h3>Crie sua conta</h3>
                            <p>Informe seu e-mail e uma senha. Escolha o perfil que corresponde à sua função na plataforma.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="landing-card h-100">
                            <span class="landing-card-icon"><i class="bi bi-calendar-check"></i></span>
                            <h3>Solicite e acompanhe</h3>
                            <p>Agende atendimentos, participe da triagem e acompanhe o status de cada visita em tempo real.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="landing-card h-100">
                            <span class="landing-card-icon"><i class="bi bi-clipboard2-pulse"></i></span>
                            <h3>Registre e cuide</h3>
                            <p>A equipe registra a consulta, o prontuário e a evolução — sempre com segurança e privacidade.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- QUEM PODE USAR -->
        <section class="landing-section landing-section-alt">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="landing-title">Feita para toda a equipe</h2>
                    <p class="text-muted">Cada profissional tem seu espaço no atendimento domiciliar.</p>
                </div>
                <div class="row g-4 justify-content-center">
                    <div class="col-md-6 col-lg-3">
                        <div class="landing-card h-100">
                            <span class="landing-card-icon"><i class="bi bi-person-badge"></i></span>
                            <h3>Médico</h3>
                            <p>Analisa triagens, avalia pendências, atende e registra o prontuário.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="landing-card h-100">
                            <span class="landing-card-icon"><i class="bi bi-bandaid"></i></span>
                            <h3>Enfermeiro</h3>
                            <p>Gerencia triagens, coordena a equipe e acompanha os atendimentos.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="landing-card h-100">
                            <span class="landing-card-icon"><i class="bi bi-heart-pulse"></i></span>
                            <h3>Técnico de Enfermagem</h3>
                            <p>Executa os cuidados domiciliares e registra cada visita.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="landing-card h-100">
                            <span class="landing-card-icon"><i class="bi bi-person-heart"></i></span>
                            <h3>Paciente</h3>
                            <p>Completa o cadastro, agenda atendimentos e acompanha sua saúde.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA FINAL -->
        <section class="landing-section">
            <div class="container">
                <div class="landing-cta text-center">
                    <h2>Pronto para começar?</h2>
                    <p>Entre com seu e-mail e senha para acessar o sistema. Ainda não tem conta? Crie uma gratuitamente.</p>
                    <a href="<?= BASE_URL ?>/login/index" class="btn btn-landing-cta btn-lg rounded-pill px-5 mt-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Entrar agora
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <span>EloMed &bull; Cuidado e atendimento em casa</span>
        <span class="text-muted small">Plataforma de atendimento domiciliar</span>
    </footer>
</body>
</html>