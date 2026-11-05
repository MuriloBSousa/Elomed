<?php
require_once __DIR__ . '/../middleware/CSRF.php';
require_once __DIR__ . '/../middleware/Session.php';
Session::start();

$currentPage = strtolower($_GET['controller'] ?? 'painel');
$currentAction = strtolower($_GET['action'] ?? 'index');
$perfil = $_SESSION['usuario_perfil'] ?? '';
$userName = $_SESSION['usuario_nome'] ?? 'Usuário';

$medicalPendings = 0;
if (RBAC::can(Constants::PERM_MEDICO_ACESSAR)) {
    $profissionalMenu ??= new Profissional();
    $profIdMenu = $profissionalMenu->getIdByUsuario($_SESSION['usuario_id'] ?? 0);
    if ($profIdMenu) {
        $medicalPendings = $profissionalMenu->contarTriagensPendentes($profIdMenu);
    }
}

$nursingPendings = 0;
if (RBAC::can(Constants::PERM_ENFERMEIRO_ACESSAR)) {
    $profissionalEnf = $profissionalMenu ?? new Profissional();
    $profIdEnf = $profissionalEnf->getIdByUsuario($_SESSION['usuario_id'] ?? 0);
    if ($profIdEnf) {
        $nursingPendings = $profissionalEnf->contarTriagensPendentesEnfermagem($profIdEnf);
    }
}

$tecnicoPendings = 0;
if (RBAC::can(Constants::PERM_TRIAGEM_ANALISAR)) {
    $profissionalTec = $profissionalMenu ?? new Profissional();
    $tecnicoPendings = $profissionalTec->contarTriagensParaAnalise();
}

$perfilLabel = perfil_meta($perfil)[0];
$perfilIcon  = perfil_meta($perfil)[1];
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/Assets/css/app.css?v=<?= @filemtime(__DIR__ . '/../../public/Assets/css/app.css') ?: '1' ?>">

<button class="menu-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
<div class="overlay" onclick="toggleSidebar()"></div>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-topo">
        <a class="sidebar-logo" href="<?= BASE_URL ?>/painel/index">
            <span class="logo-icon"><i class="bi bi-activity"></i></span>
            <span>EloMed</span>
        </a>
        <form method="post" action="<?= BASE_URL ?>/login/sair" class="d-inline">
            <?= CSRF::getField() ?>
            <button type="submit" class="logout-topo" id="btnSair" title="Sair do sistema">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sair</span>
            </button>
        </form>
    </div>

    <div class="titulo-side">Menu Principal</div>

    <a class="item <?= ($currentPage == 'painel') ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/painel/index">
        <span><i class="bi bi-grid-1x2"></i> Dashboard</span>
    </a>

    <a class="item <?= ($currentPage == 'perfil') ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/perfil/completar_cadastro">
        <span><i class="bi bi-person-vcard"></i> Meu Cadastro</span>
    </a>

    <a class="item <?= ($currentPage == 'perfil' && $currentAction == 'trocar_senha') ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/perfil/trocar_senha">
        <span><i class="bi bi-key"></i> Alterar Senha</span>
    </a>

    <?php if (RBAC::can(Constants::PERM_MEDICO_ACESSAR) && RBAC::hasRole(Constants::PERFIL_MEDICO)): ?>
    <div class="menu-item">
        <div class="item has-submenu <?= ($currentPage == 'medico') ? 'active' : '' ?>"
             onclick="toggleSubmenu('medico-submenu')">
            <span><i class="bi bi-heart-pulse"></i> Médico</span>
        </div>
        <div id="medico-submenu" class="submenu <?= ($currentPage == 'medico') ? 'open' : '' ?>">
            <a href="<?= BASE_URL ?>/medico/index"
               class="<?= ($currentPage == 'medico' && $currentAction == 'index') ? 'active' : '' ?>">Dashboard</a>
            <a href="<?= BASE_URL ?>/medico/pendencias"
               class="<?= ($currentPage == 'medico' && $currentAction == 'pendencias') ? 'active' : '' ?>">Pendências
               <?php if ($medicalPendings > 0): ?><span class="badge bg-danger ms-1"><?= $medicalPendings ?></span><?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/medico/minha_agenda"
               class="<?= ($currentPage == 'medico' && $currentAction == 'minha_agenda') ? 'active' : '' ?>">Minha Agenda</a>
            <a href="<?= BASE_URL ?>/medico/historico_visitas"
               class="<?= ($currentPage == 'medico' && $currentAction == 'historico_visitas') ? 'active' : '' ?>">Histórico de Visitas</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (RBAC::can(Constants::PERM_ENFERMEIRO_ACESSAR) && RBAC::hasRole(Constants::PERFIL_ENFERMEIRO)): ?>
    <div class="menu-item">
        <div class="item has-submenu <?= ($currentPage == 'enfermeiro') ? 'active' : '' ?>"
             onclick="toggleSubmenu('enfermeiro-submenu')">
            <span><i class="bi bi-person-fill-check"></i> Enfermeiro
             <?php if ($nursingPendings > 0): ?><span class="badge bg-danger ms-1"><?= $nursingPendings ?></span><?php endif; ?>
            </span>
        </div>
        <div id="enfermeiro-submenu" class="submenu <?= ($currentPage == 'enfermeiro') ? 'open' : '' ?>">
            <a href="<?= BASE_URL ?>/enfermeiro/index"
               class="<?= ($currentPage == 'enfermeiro' && $currentAction == 'index') ? 'active' : '' ?>">Dashboard</a>
            <a href="<?= BASE_URL ?>/enfermeiro/pendencias"
               class="<?= ($currentPage == 'enfermeiro' && $currentAction == 'pendencias') ? 'active' : '' ?>">
                Pendências
                <?php if ($nursingPendings > 0): ?><span class="badge bg-danger ms-1"><?= $nursingPendings ?></span><?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/enfermeiro/minha_agenda"
               class="<?= ($currentPage == 'enfermeiro' && $currentAction == 'minha_agenda') ? 'active' : '' ?>">Minha Agenda</a>
            <a href="<?= BASE_URL ?>/enfermeiro/historico"
               class="<?= ($currentPage == 'enfermeiro' && $currentAction == 'historico') ? 'active' : '' ?>">Histórico</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (RBAC::can(Constants::PERM_TECNICO_ACESSAR) && RBAC::hasRole(Constants::PERFIL_TECNICO_ENFERMAGEM )): ?>
    <div class="menu-item">
        <div class="item has-submenu <?= ($currentPage == 'tecnicoenfermagem') ? 'active' : '' ?>"
             onclick="toggleSubmenu('tecnico-submenu')">
            <span><i class="bi bi-clipboard2-pulse"></i> Técnico Enfermagem</span>
        </div>
        <div id="tecnico-submenu" class="submenu <?= ($currentPage == 'tecnicoenfermagem') ? 'open' : '' ?>">
            <a href="<?= BASE_URL ?>/tecnicoenfermagem/index"
               class="<?= ($currentPage == 'tecnicoenfermagem' && $currentAction == 'index') ? 'active' : '' ?>">Dashboard</a>
            <a href="<?= BASE_URL ?>/tecnicoenfermagem/triagens"
               class="<?= ($currentPage == 'tecnicoenfermagem' && $currentAction == 'triagens') ? 'active' : '' ?>">Triagens
               <?php if ($tecnicoPendings > 0): ?><span class="badge bg-danger ms-1"><?= $tecnicoPendings ?></span><?php endif; ?>
            </a>
            <a href="<?= BASE_URL ?>/tecnicoenfermagem/minha_agenda"
               class="<?= ($currentPage == 'tecnicoenfermagem' && $currentAction == 'minha_agenda') ? 'active' : '' ?>">Minha Agenda</a>
            <a href="<?= BASE_URL ?>/tecnicoenfermagem/historico"
               class="<?= ($currentPage == 'tecnicoenfermagem' && $currentAction == 'historico') ? 'active' : '' ?>">Histórico</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (RBAC::can(Constants::PERM_PRONTUARIO_PACIENTE_ACESSAR)): ?>
    <a class="item <?= ($currentPage == 'prontuario') ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/prontuario/buscar">
        <span><i class="bi bi-journal-medical"></i> Prontuários</span>
    </a>
    <?php endif; ?>

    <?php if (RBAC::can(Constants::PERM_PACIENTE_ACESSAR) && RBAC::hasRole(Constants::PERFIL_PACIENTE)): ?>
    <div class="menu-item">
        <div class="item has-submenu <?= ($currentPage == 'paciente') ? 'active' : '' ?>"
             onclick="toggleSubmenu('paciente-submenu')">
            <span><i class="bi bi-person-heart"></i> Paciente</span>
        </div>
        <div id="paciente-submenu" class="submenu <?= ($currentPage == 'paciente') ? 'open' : '' ?>">
            <a href="<?= BASE_URL ?>/paciente/index"
               class="<?= ($currentPage == 'paciente' && $currentAction == 'index') ? 'active' : '' ?>">Dashboard</a>
            <?php if (RBAC::hasRole(Constants::PERFIL_PACIENTE)): ?>
            <a href="<?= BASE_URL ?>/paciente/completar_cadastro"
               class="<?= ($currentPage == 'paciente' && $currentAction == 'completar_cadastro') ? 'active' : '' ?>">
                <i class="bi bi-person-vcard"></i> Completar Cadastro
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/paciente/minhas_triagens"
               class="<?= ($currentPage == 'paciente' && $currentAction == 'minhas_triagens') ? 'active' : '' ?>">Minhas Triagens</a>
            <a href="<?= BASE_URL ?>/agendamento/solicitar_agendamento"
               class="<?= ($currentPage == 'agendamento') ? 'active' : '' ?>">Solicitar Agendamento</a>
            <a href="<?= BASE_URL ?>/paciente/agendamento"
               class="<?= ($currentPage == 'paciente' && $currentAction == 'agendamento') ? 'active' : '' ?>">Meus Agendamentos</a>
            <a href="<?= BASE_URL ?>/paciente/historico"
               class="<?= ($currentPage == 'paciente' && $currentAction == 'historico') ? 'active' : '' ?>">Histórico</a>
            <a href="<?= BASE_URL ?>/paciente/linha_do_tempo"
               class="<?= ($currentPage == 'paciente' && $currentAction == 'linha_do_tempo') ? 'active' : '' ?>">Linha do Tempo</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (RBAC::can(Constants::PERM_USUARIO_GERENCIAR)): ?>
    <a class="item <?= ($currentPage == 'usuario') ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/usuario/index">
        <span><i class="bi bi-people"></i> Usuários</span>
    </a>
    <a class="item <?= ($currentPage == 'auditoria' && $currentAction == 'monitoramento') ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/auditoria/monitoramento">
        <span><i class="bi bi-graph-up-arrow"></i> Monitoramento</span>
    </a>
    <a class="item <?= ($currentPage == 'auditoria' && $currentAction == 'index') ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/auditoria/index">
        <span><i class="bi bi-journal-text"></i> Trilha de Auditoria</span>
    </a>
    <?php endif; ?>

    <?php if (RBAC::can(Constants::PERM_FEEDBACK_VISUALIZAR)): ?>
    <a class="item <?= ($currentPage == 'painel' && $currentAction == 'feedback') ? 'active' : '' ?>"
       href="<?= BASE_URL ?>/painel/feedback">
        <span><i class="bi bi-star-half"></i> Avaliações</span>
    </a>
    <?php endif; ?>

    <div class="menu-separator"></div>

    <div class="sidebar-user">
        <div class="avatar"><i class="bi <?= $perfilIcon ?>"></i></div>
        <div class="user-infos">
            <strong><?= e($userName) ?></strong>
            <small><?= $perfilLabel ?></small>
        </div>
    </div>
</nav>

<div class="modal-sair" id="modalSair" aria-hidden="true">
    <div class="modal-sair-overlay" data-sair-fechar></div>
    <div class="modal-sair-dialog" role="dialog" aria-modal="true" aria-labelledby="modalSairTitulo">
        <div class="modal-sair-icon"><i class="bi bi-box-arrow-right"></i></div>
        <h3 id="modalSairTitulo">Sair do sistema?</h3>
        <p>Você está prestes a encerrar sua sessão no EloMed. Para continuar, será preciso entrar novamente.</p>
        <div class="modal-sair-actions">
            <button type="button" class="btn-sair-cancelar" data-sair-fechar>Cancelar</button>
            <form method="post" action="<?= BASE_URL ?>/login/sair" class="d-inline">
                <?= CSRF::getField() ?>
                <button type="submit" class="btn-sair-confirmar">
                    <i class="bi bi-box-arrow-right"></i> Sair
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/Assets/js/toasts.js"></script>
<script src="<?= BASE_URL ?>/Assets/js/select-profissional.js"></script>
<?php
require_once __DIR__ . '/../helpers/flash_toasts.php';
flash_toasts();
?>
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.overlay');
    const abrir = !sidebar.classList.contains('open');
    sidebar.classList.toggle('open', abrir);
    // O overlay (que embaça e captura os toques) so fica ativo com o menu aberto.
    overlay.classList.toggle('active', abrir);
}

function fecharSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.querySelector('.overlay').classList.remove('active');
}

function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    submenu.classList.toggle('open');
    document.querySelectorAll('.submenu').forEach(sm => {
        if (sm.id !== id) sm.classList.remove('open');
    });
}

document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 992 && link.id !== 'btnSair') fecharSidebar();
    });
});

// Ao voltar para o desktop, garante o menu aberto e o overlay desligado.
window.addEventListener('resize', () => {
    if (window.innerWidth > 992) fecharSidebar();
});

// Fecha o menu (e desliga o overlay embaçado) com a tecla Esc.
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('sidebar').classList.contains('open')) {
        fecharSidebar();
    }
});

const btnSair = document.getElementById('btnSair');
const modalSair = document.getElementById('modalSair');

function abrirModalSair() {
    modalSair.classList.add('open');
    modalSair.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function fecharModalSair() {
    modalSair.classList.remove('open');
    modalSair.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

if (btnSair && modalSair) {
    btnSair.addEventListener('click', (e) => {
        e.preventDefault();
        abrirModalSair();
    });

    modalSair.querySelectorAll('[data-sair-fechar]').forEach(el => {
        el.addEventListener('click', fecharModalSair);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalSair.classList.contains('open')) fecharModalSair();
    });
}
</script>
