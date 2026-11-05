<div class="pesquisa mb-3">
    <form class="row g-2 align-items-end" action="<?= BASE_URL ?>/usuario/listar" method="GET">

        <div class="col-6 col-md-2 col-lg-1">
            <label class="form-label small mb-1" for="filtro-id">ID</label>
            <input class="form-control form-control-sm" type="number" name="id"
                   id="filtro-id" min="1" placeholder="#"
                   value="<?= e($_GET['id'] ?? '') ?>" aria-label="ID">
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label small mb-1" for="filtro-nome">Nome</label>
            <input class="form-control form-control-sm" type="search" name="nome"
                   id="filtro-nome" placeholder="Iniciais do nome..." aria-label="Nome"
                   value="<?= e($_GET['nome'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label small mb-1" for="filtro-email">E-mail</label>
            <input class="form-control form-control-sm" type="search" name="email"
                   id="filtro-email" placeholder="E-mail..." aria-label="E-mail"
                   value="<?= e($_GET['email'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label small mb-1" for="filtro-perfil">Perfil</label>
            <select class="form-select form-select-sm" name="perfil" id="filtro-perfil">
                <option value="">Todos os perfis</option>
                <?php
                    $perfis = [
                        Constants::PERFIL_ADMIN             => 'Administrador',
                        Constants::PERFIL_MEDICO            => 'Médico',
                        Constants::PERFIL_ENFERMEIRO        => 'Enfermeiro',
                        Constants::PERFIL_TECNICO_ENFERMAGEM => 'Técnico de Enfermagem',
                        Constants::PERFIL_PACIENTE          => 'Paciente',
                    ];
                    $perfilAtual = $_GET['perfil'] ?? '';
                    foreach ($perfis as $valor => $rotulo):
                ?>
                    <option value="<?= e($valor) ?>" <?= $perfilAtual === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 col-md-6 col-lg-2">
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success btn-sm flex-fill" type="submit">
                    <i class="bi bi-search me-1"></i> Buscar
                </button>
                <a href="<?= BASE_URL ?>/usuario/index" class="btn btn-outline-secondary btn-sm flex-fill">
                    <i class="bi bi-x-circle me-1"></i> Limpar
                </a>
            </div>
        </div>
    </form>
</div>