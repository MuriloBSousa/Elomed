<?php
$endereco = $endereco ?: [];
$telefones = $telefones ?: [];
$contadores = $contadores ?: [];
$anamnese = $anamnese ?: [];
$telPrincipal = '';
foreach ($telefones as $t) {
    if (($t['tipo'] ?? '') === 'principal') { $telPrincipal = $t['telefone']; break; }
}
// Formas de exibicao: o banco guarda so digito (CHAR), a tela mostra
// com mascara. e() escapa, o resto e formatacao.
$cpfTela = $paciente['cpf'] ?? '';
if ($cpfTela !== '') {
    $cpfTela = substr($cpfTela,0,3).'.'.substr($cpfTela,3,3).'.'.substr($cpfTela,6,3).'-'.substr($cpfTela,9,2);
}
$cepTela = $endereco['cep'] ?? '';
if ($cepTela !== '') {
    $cepTela = substr($cepTela,0,5).'-'.substr($cepTela,5);
}
$telTela = $telPrincipal !== ''
    ? '(' . substr($telPrincipal,0,2) . ') ' . substr($telPrincipal,2,5) . '-' . substr($telPrincipal,7)
    : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prontuario de <?= e($usuario['nome']) ?> - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1><?= e($usuario['nome']) ?></h1>
        <p>Prontuario do paciente - os lancamentos sao permanentes e tem autor e horario.</p>
    </header>

    <?php
    // As quatro paginas do prontuario. "dados" nao tem secao no banco:
    // e a leitura dos dados cadastrais que ja existem em
    // tbl_paciente / tbl_endereco / tbl_telefone.
    ?>
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="<?= BASE_URL ?>/prontuario/dados/<?= (int)$paciente['id'] ?>">
                <i class="bi bi-person-vcard me-1"></i> Dados do paciente
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/prontuario/secao/<?= (int)$paciente['id'] ?>/anamnese">
                <i class="bi bi-clipboard2-pulse me-1"></i> Anamnese
                <span class="badge bg-secondary"><?= (int)($contadores['anamnese'] ?? 0) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/prontuario/secao/<?= (int)$paciente['id'] ?>/evolucao">
                <i class="bi bi-activity me-1"></i> Evolucao e Tratamento
                <span class="badge bg-secondary"><?= (int)($contadores['evolucao'] ?? 0) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/prontuario/secao/<?= (int)$paciente['id'] ?>/complementar">
                <i class="bi bi-paperclip me-1"></i> Informacoes Complementares
                <span class="badge bg-secondary"><?= (int)($contadores['complementar'] ?? 0) ?></span>
            </a>
        </li>
    </ul>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="section-title"><i class="bi bi-fingerprint"></i> Identificacao</h2>
            <p class="sem-dados small">
                Estes campos vem de tbl_usuario e tbl_paciente e sao de
                leitura. Alteracoes sao feitas no cadastro do paciente.
            </p>
            <div class="row">
                <div class="col-md-6"><strong>Nome:</strong> <?= e($usuario['nome']) ?></div>
                <div class="col-md-6"><strong>E-mail:</strong> <?= e($usuario['email']) ?></div>
                <div class="col-md-6"><strong>Nascimento:</strong>
                    <?= $paciente['data_nascimento'] ? e(date('d/m/Y', strtotime($paciente['data_nascimento']))) : '-' ?>
                </div>
                <div class="col-md-6"><strong>Telefone cadastrado:</strong>
                    <?= e($telTela ?: '-') ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($anamnese): ?>
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="section-title"><i class="bi bi-journal-medical"></i> Anamnese de cadastro</h2>
            <p class="sem-dados small">
                Esta e a ficha preenchida no cadastro do paciente
                (tbl_anamnese). Os lancamentos de consulta ficam na aba
                Anamnese do prontuario.
            </p>
            <div class="row">
                <div class="col-md-6"><strong>Alergias:</strong><br><?= nl2br(e($anamnese['alergias'] ?: '-')) ?></div>
                <div class="col-md-6"><strong>Doencas previas:</strong><br><?= nl2br(e($anamnese['doencas_previas'] ?: '-')) ?></div>
                <div class="col-md-6"><strong>Medicamentos:</strong><br><?= nl2br(e($anamnese['medicamentos_uso'] ?: '-')) ?></div>
                <div class="col-md-6"><strong>Cirurgias:</strong><br><?= nl2br(e($anamnese['cirurgias'] ?: '-')) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (RBAC::can(Constants::PERM_PRONTUARIO_PACIENTE_REGISTRAR)): ?>
    <form method="post" action="<?= BASE_URL ?>/prontuario/salvar_dados/<?= (int)$paciente['id'] ?>">
        <?= CSRF::getField() ?>
        <div class="card">
            <div class="card-body">
                <h2 class="section-title"><i class="bi bi-pencil-square"></i> Dados editaveis</h2>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="cpf">CPF</label>
                        <input type="text" class="form-control campo-cpf" id="cpf" name="cpf"
                               value="<?= e($cpfTela) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="rg">RG</label>
                        <input type="text" class="form-control" id="rg" name="rg"
                               value="<?= e($paciente['rg'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="telefone_principal">Telefone</label>
                        <input type="text" class="form-control campo-telefone" id="telefone_principal"
                               name="telefone_principal" value="<?= e($telTela) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="sexo">Sexo</label>
                        <select class="form-select" id="sexo" name="sexo">
                            <?php foreach (['feminino','masculino','nao_informado'] as $op): ?>
                                <option value="<?= e($op) ?>"
                                        <?= (($paciente['sexo'] ?? '') === $op) ? 'selected' : '' ?>>
                                    <?= e(ucfirst(str_replace('_', ' ', $op))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="estado_civil">Estado civil</label>
                        <select class="form-select" id="estado_civil" name="estado_civil">
                            <?php foreach (['solteiro','casado','divorciado','viuvo','uniao_estavel','separado','nao_informado'] as $op): ?>
                                <option value="<?= e($op) ?>"
                                        <?= (($paciente['estado_civil'] ?? '') === $op) ? 'selected' : '' ?>>
                                    <?= e(ucfirst(str_replace('_', ' ', $op))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <hr class="my-4">
                <h3 class="section-title"><i class="bi bi-geo-alt"></i> Endereco</h3>
                <p class="sem-dados small">
                    Preencha o CEP e o endereco e preenchido sozinho. Se o
                    CEP nao existir, o sistema avisa e voce continua
                    digitando na mao.
                </p>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="endereco_cep">CEP</label>
                        <input type="text" class="form-control campo-cep" id="endereco_cep"
                               name="endereco_cep" value="<?= e($cepTela) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="endereco_rua">Rua</label>
                        <input type="text" class="form-control" id="endereco_rua" name="endereco_rua"
                               value="<?= e($endereco['logradouro'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="endereco_numero">Numero</label>
                        <input type="text" class="form-control" id="endereco_numero" name="endereco_numero"
                               value="<?= e($endereco['numero'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="endereco_complemento">Complemento</label>
                        <input type="text" class="form-control" id="endereco_complemento"
                               name="endereco_complemento"
                               value="<?= e($endereco['complemento'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="endereco_bairro">Bairro</label>
                        <input type="text" class="form-control" id="endereco_bairro" name="endereco_bairro"
                               value="<?= e($endereco['bairro'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="endereco_cidade">Cidade</label>
                        <input type="text" class="form-control" id="endereco_cidade" name="endereco_cidade"
                               value="<?= e($endereco['cidade'] ?? '') ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label" for="endereco_uf">UF</label>
                        <input type="text" class="form-control" id="endereco_uf" name="endereco_uf"
                               maxlength="2" value="<?= e($endereco['uf'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Salvar dados
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script src="<?= BASE_URL ?>/Assets/js/mascaras.js"></script>
<script src="<?= BASE_URL ?>/Assets/js/buscar_cep.js"></script>
</body>
</html>

<script src="<?= BASE_URL ?>/Assets/js/buscar_cpf.js"></script>
