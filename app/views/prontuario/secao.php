<?php
$secaoAtual = (string)($secao ?? '');
$titulo = ProntuarioRegistro::SECOES[$secaoAtual] ?? 'Secao';
$contadores = $contadores ?: [];
$registros = $registros ?: [];
$ehMedico = RBAC::hasRole(Constants::PERFIL_MEDICO)
         || RBAC::hasRole(Constants::PERFIL_ADMIN);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> - <?= e($usuario['nome']) ?> - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1><?= e($usuario['nome']) ?></h1>
        <p><?= e($titulo) ?> - <?= (int)$total ?> registro(s). O que e finalizado
           nao pode ser alterado: cada consulta vira uma linha nova.</p>
    </header>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/prontuario/dados/<?= (int)$paciente['id'] ?>">
                <i class="bi bi-person-vcard me-1"></i> Dados do paciente
            </a>
        </li>
        <?php foreach (ProntuarioRegistro::SECOES as $chave => $rotulo): ?>
            <li class="nav-item">
                <a class="nav-link <?= $chave === $secaoAtual ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>/prontuario/secao/<?= (int)$paciente['id'] ?>/<?= e($chave) ?>">
                    <i class="bi bi-journal-text me-1"></i> <?= e($rotulo) ?>
                    <span class="badge bg-secondary"><?= (int)($contadores[$chave] ?? 0) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if (RBAC::can(Constants::PERM_PRONTUARIO_PACIENTE_REGISTRAR)): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="section-title"><i class="bi bi-plus-circle"></i> Novo registro</h2>
            <form method="post" id="formRegistro"
                  action="<?= BASE_URL ?>/prontuario/registrar/<?= (int)$paciente['id'] ?>">
                <?= CSRF::getField() ?>
                <input type="hidden" name="secao" value="<?= e($secaoAtual) ?>">
                <input type="hidden" name="fk_agendamento"
                       id="agendamentoAtual" value="<?= (int)($_GET['agendamento'] ?? 0) ?>">

                <div class="mb-3">
                    <label class="form-label" for="conteudo"><?= e($titulo) ?></label>
                    <textarea class="form-control" id="conteudo" name="conteudo" rows="6"
                              required minlength="10"
                              placeholder="Escreva o que voce observou nesta consulta. Minimo de 10 caracteres."></textarea>
                    <div class="form-text">
                        <span id="contador">0</span> caracteres. O registro fica permanentemente no historico.
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="data_hora">Data do atendimento</label>
                        <input type="datetime-local" class="form-control" id="data_hora"
                               name="data_hora" value="">
                        <div class="form-text">
                            Em branco = agora. Preencha so quando estiver
                            registrando um atendimento de outro dia.
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <?php if ($ehMedico): ?>
                        <button type="submit" name="status" value="<?= e(Constants::PRONTUARIO_STATUS_RASCUNHO) ?>"
                                class="btn btn-outline-secondary" data-enviar="rascunho">
                            <i class="bi bi-file-earmark me-1"></i> Salvar rascunho
                        </button>
                    <?php endif; ?>
                    <button type="submit" name="status" value="<?= e(Constants::PRONTUARIO_STATUS_FINALIZADO) ?>"
                            class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Finalizar e salvar
                    </button>
                </div>
                <p class="sem-dados small mt-2 mb-0">
                    Rascunho voce pode editar ou apagar. Finalizado nao.
                </p>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$registros): ?>
        <p class="sem-dados">
            <i class="bi bi-inbox"></i> Nenhum registro em <?= e($titulo) ?> ainda.
        </p>
    <?php else: ?>
        <div class="table-container">
            <h2><i class="bi bi-clock-history text-primary"></i> Historico</h2>
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th style="width: 150px;">Data</th>
                        <th>Registro</th>
                        <th style="width: 200px;">Autor</th>
                        <th style="width: 120px;">Consulta</th>
                        <th style="width: 160px;">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($registros as $r): ?>
                    <?php
                    $rascunho = $r['status'] === Constants::PRONTUARIO_STATUS_RASCUNHO;
                    $meuRascunho = $rascunho
                        && (int)$r['fk_usuario'] === (int)Session::get('usuario_id');
                    ?>
                    <tr>
                        <td>
                            <div><?= e(date('d/m/Y', strtotime($r['data_hora']))) ?></div>
                            <div class="small text-muted"><?= e(date('H:i', strtotime($r['data_hora']))) ?></div>
                        </td>
                        <td>
                            <div style="white-space: pre-wrap;"><?= e($r['conteudo']) ?></div>
                            <?php if ($rascunho): ?>
                                <span class="badge bg-warning text-dark mt-1">
                                    <i class="bi bi-pencil me-1"></i> Rascunho
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success mt-1">
                                    <i class="bi bi-lock me-1"></i> Finalizado
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= e($r['autor_nome']) ?></div>
                            <?php if (!empty($r['autor_especialidade'])): ?>
                                <div class="small text-muted"><?= e($r['autor_especialidade']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($r['autor_registro'])): ?>
                                <div class="small text-muted"><?= e($r['autor_registro']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($r['atendimento_inicio'])): ?>
                                <?= e(date('d/m/Y', strtotime($r['atendimento_inicio']))) ?>
                                <span class="small text-muted">
                                    <?= e(substr((string)$r['atendimento_inicio'], 11, 5)) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($meuRascunho): ?>
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= BASE_URL ?>/prontuario/editar_registro/<?= (int)$r['id'] ?>">
                                    <i class="bi bi-pencil me-1"></i> Editar
                                </a>
                                <form method="post" class="d-inline"
                                      action="<?= BASE_URL ?>/prontuario/finalizar_registro/<?= (int)$r['id'] ?>">
                                    <?= CSRF::getField() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-lock me-1"></i> Finalizar
                                    </button>
                                </form>
                                <form method="post" class="d-inline"
                                      action="<?= BASE_URL ?>/prontuario/excluir_registro/<?= (int)$r['id'] ?>"
                                      data-confirmar="Excluir este rascunho?">
                                    <?= CSRF::getField() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash me-1"></i> Excluir
                                    </button>
                                </form>
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

<script src="<?= BASE_URL ?>/Assets/js/mascaras.js"></script>
<script>
// Contador de caracteres do registro.
(function () {
    var area = document.getElementById('conteudo');
    var saida = document.getElementById('contador');
    if (!area || !saida) { return; }
    function contar() { saida.textContent = area.value.length; }
    area.addEventListener('input', contar);
    contar();
})();

// Confirmacao antes de excluir um rascunho (o resto do projeto usa
// data-confirmar; este listener e o unico lugar que precisa).
document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        if (!window.confirm(form.getAttribute('data-confirmar'))) {
            e.preventDefault();
        }
    });
});
</script>
</body>
</html>
