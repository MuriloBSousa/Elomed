<?php
require_once __DIR__ . '/../../middleware/CSRF.php';
require_once __DIR__ . '/../../middleware/Session.php';
Session::start();

$perfil = Session::get('usuario_perfil');
$perfilInicial = perfil_meta($perfil);

$placeholderRegistro = [
    'medico'             => 'CRM',
    'enfermeiro'         => 'COREN',
    'tecnico_enfermagem' => 'COREN'
];

$avatarPadrao = "data:image/svg+xml;charset=utf-8," . rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' width='110' height='110' viewBox='0 0 110 110'><rect width='110' height='110' fill='#eef2ff'/><circle cx='55' cy='44' r='22' fill='#4f46e5'/><path d='M13 100c4-21 21-30 42-30s38 9 42 30' fill='#4f46e5'/></svg>");
$ehProfissional = in_array($perfil, ['medico', 'enfermeiro', 'tecnico_enfermagem'], true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Cadastro - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1><i class="bi bi-person-vcard text-primary"></i> Meu Cadastro</h1>
        <p>Complete ou atualize seus dados cadastrais.</p>
    </header>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="perfil-card text-center">
                <?php if ($ehProfissional): ?>
                    <?php $fotoAtual = $profissional['foto'] ?? ''; ?>
                    <img id="previewFoto"
                         src="<?= !empty($fotoAtual) ? BASE_URL . '/' . e($fotoAtual) : $avatarPadrao ?>"
                         alt="Foto do profissional" class="avatar-photo mx-auto d-block"
                         onerror="this.src='<?= $avatarPadrao ?>'">
                <?php else: ?>
                    <div class="avatar avatar-lg mx-auto"><i class="bi <?= $perfilInicial[1] ?>"></i></div>
                <?php endif; ?>
                <h3 class="h5 fw-bold mt-3 mb-0"><?= e($usuario['nome'] ?? Session::get('usuario_nome')) ?></h3>
                <p class="text-muted small mb-2"><?= $perfilInicial[0] ?></p>
                <p class="small text-muted mb-0"><i class="bi bi-envelope me-1"></i><?= e($usuario['email'] ?? '') ?></p>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if ($perfil === 'admin'): ?>
                <div class="alert alert-info d-flex align-items-start py-2">
                    <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                    <div>Como administrador seu cadastro já está completo. Você pode atualizar seu nome,
                        e-mail e senha neste formulário, além de gerenciar todos os usuários do sistema
                        pelo menu <strong>Usuários</strong>.</div>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <h2><i class="bi bi-pencil-square text-primary"></i> Dados de <?= $perfilInicial[0] ?></h2>
                <form method="post" action="<?= BASE_URL ?>/perfil/salvar" enctype="multipart/form-data">
                    <?= CSRF::getField() ?>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Nome completo</label>
                            <input type="text" name="nome" class="form-control" required minlength="3"
                                   value="<?= e($usuario['nome'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= e($usuario['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Nova senha <small class="text-muted">(opcional — deixe em branco para manter)</small></label>
                            <input type="password" name="senha" class="form-control" minlength="8"
                                   placeholder="Mínimo 8 caracteres, com letras e números">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Senha atual <small class="text-muted">(obrigatória para alterar)</small></label>
                            <input type="password" name="senha_atual" class="form-control">
                        </div>

                        <?php if (in_array($perfil, ['medico', 'enfermeiro', 'tecnico_enfermagem'], true)): ?>
                        <div class="col-md-6">
                            <label class="form-label">Especialidade / Atuação</label>
                            <input type="text" name="especialidade" class="form-control" required minlength="3"
                                   value="<?= e($profissional['especialidade'] ?? '') ?>"
                                   placeholder="Ex.: Clínico Geral, Cardiologia">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nº de Registro (<?= $placeholderRegistro[$perfil] ?>)</label>
                            <input type="text" name="numero_registro" class="form-control" required
                                   value="<?= e($profissional['numero_registro'] ?? '') ?>"
                                   placeholder="Ex.: CRM 000000-SP">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Foto de perfil <small class="text-muted">(JPG, PNG ou WEBP — máx. 2MB)</small></label>
                            <input type="file" name="foto" id="inputFoto" class="form-control"
                                   accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Exibida para quem for te selecionar em agendamentos e triagens.</div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-check-lg me-1"></i> Salvar cadastro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var input = document.getElementById('inputFoto');
    var preview = document.getElementById('previewFoto');
    var padrao = '<?= $avatarPadrao ?>';
    if (!input || !preview) return;
    input.addEventListener('change', function () {
        var arquivo = this.files && this.files[0];
        if (!arquivo) { return; }
        if (arquivo.size > 2 * 1024 * 1024) {
            alert('A foto deve ter no máximo 2MB.');
            this.value = '';
            return;
        }
        var leitor = new FileReader();
        leitor.onload = function (e) { preview.src = e.target.result; };
        leitor.readAsDataURL(arquivo);
    });
    preview.addEventListener('error', function () { this.src = padrao; });
})();
</script>
</body>
</html>