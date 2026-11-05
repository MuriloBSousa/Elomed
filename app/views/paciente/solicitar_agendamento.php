<?php
Session::start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Agendamento - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Formulário de Agendamento</h1>
        <p>Solicite um novo agendamento médico.</p>
    </header>

    <?php if (isset($erro)): ?>
        <div class="alert alert-danger"><?= e($erro) ?></div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/agendamento/enviar_dados" method="POST">
        <?= CSRF::getField() ?>

        <?php if (!empty($ehAdmin)): ?>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="fk_usuario">Paciente:</label>
                <select name="fk_usuario" id="fk_usuario" required class="form-select">
                    <option value="">-- Escolha o paciente --</option>
                    <?php foreach ($pacientes as $pac): ?>
                        <option value="<?= (int)$pac['fk_usuario'] ?>"><?= e($pac['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else: ?>
            <input type="hidden" name="fk_usuario" value="<?= (int)$fk_usuario_hidden ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Data:</label>
                    <input type="date" name="data" required class="form-control" placeholder="dd/mm/aaaa">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Horário do início da visita:</label>
                    <input type="time" name="horario_inicio" required class="form-control" placeholder="00:00">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Horário do fim da visita:</label>
                    <input type="time" name="horario_fim" required class="form-control" placeholder="00:00">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label" for="fk_profissional">Selecione o Profissional de Saúde:</label>
                    <select name="fk_profissional" id="fk_profissional" required
                            class="form-select js-seletor-profissional" data-titulo="Escolha quem vai te atender">
                        <option value="">-- Escolha quem vai te atender --</option>
                        <?php if (!empty($profissionais) && is_array($profissionais)): ?>
                            <?php foreach ($profissionais as $prof):
                                $descProf = trim(implode(' · ', array_filter([
                                    $prof['especialidade'] ?? '',
                                    !empty($prof['tempo_experiencia_anos']) ? (int) $prof['tempo_experiencia_anos'] . ' anos' : '',
                                    !empty($prof['numero_registro']) ? $prof['numero_registro'] : ''
                                ])));
                                $fotoProf = !empty($prof['foto']) ? BASE_URL . '/' . $prof['foto'] : '';
                            ?>
                                <option value="<?= (int)$prof['id_profissional'] ?>"
                                        data-nome="<?= e($prof['nome_profissional']) ?>"
                                        data-curto="<?= e($prof['nome_profissional']) ?>"
                                        data-descricao="<?= e($descProf) ?>"
                                        data-foto="<?= e($fotoProf) ?>">
                                    <?= e($prof['nome_profissional']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Nenhum profissional disponível no momento</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Solicitar</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.href='<?= BASE_URL ?>/paciente/index'">Voltar</button>
        </div>
    </form>
</div>
</body>
</html>
