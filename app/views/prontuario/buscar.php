<?php
// A view NAO consulta o banco: ela so desenha o que o controller
// passou. Por isso o ?? [] - se alguem abrir a view sem ir pelo
// controller, ela mostra "nenhum resultado" em vez de quebrar.
$resultados = $resultados ?? [];
$termo = $termo ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prontuários - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Prontuario do paciente</h1>
        <p>Busque o paciente pelo nome ou pelo CPF para abrir o prontuario.</p>
    </header>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?= BASE_URL ?>/prontuario/buscar" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label" for="termo">Nome ou CPF</label>
                    <input type="text" class="form-control" id="termo" name="termo"
                           placeholder="Maria Souza ou 123.456.789-00"
                           value="<?= e($termo ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (mb_strlen(trim((string)$termo)) < 2): ?>
        <p class="sem-dados">
            <i class="bi bi-inboxes"></i> Digite ao menos 2 letras do nome
            ou os 11 digitos do CPF.
        </p>
    <?php else: ?>
        <?php if (!$resultados): ?>
            <p class="sem-dados">
                <i class="bi bi-inbox"></i> Nenhum paciente encontrado para "<?= e($termo) ?>".
            </p>
        <?php else: ?>
        <div class="table-container">
            <h2><i class="bi bi-person-lines-fill text-primary"></i> Pacientes</h2>
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>E-mail</th>
                        <th class="text-end">Acao</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($resultados as $r): ?>
                    <tr>
                        <td><?= e($r['nome']) ?></td>
                        <td><?= e($r['cpf'] ?: '-') ?></td>
                        <td><?= e($r['email']) ?></td>
                        <td class="text-end">
                            <?php if (!empty($r['id_paciente'])): ?>
                                <a class="btn btn-sm btn-primary"
                                   href="<?= BASE_URL ?>/prontuario/dados/<?= (int)$r['id_paciente'] ?>">
                                    <i class="bi bi-journal-medical me-1"></i> Abrir prontuario
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">Cadastro incompleto</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
