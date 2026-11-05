<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar rascunho - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Editar rascunho</h1>
        <p>
            <?= e($usuario['nome']) ?> -
            <?= e(ProntuarioRegistro::SECOES[$registro['secao']] ?? $registro['secao']) ?>
        </p>
    </header>

    <div class="card">
        <div class="card-body">
            <form method="post"
                  action="<?= BASE_URL ?>/prontuario/salvar_registro/<?= (int)$registro['id'] ?>">
                <?= CSRF::getField() ?>
                <div class="mb-3">
                    <label class="form-label" for="conteudo">Registro</label>
                    <textarea class="form-control" id="conteudo" name="conteudo" rows="8"
                              required minlength="10"><?= e($registro['conteudo']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Salvar rascunho
                </button>
                <a class="btn btn-outline-secondary"
                   href="<?= BASE_URL ?>/prontuario/secao/<?= (int)$registro['fk_paciente'] ?>/<?= e($registro['secao']) ?>">
                    <i class="bi bi-arrow-left me-1"></i> Voltar
                </a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
