<?php
Session::start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Paciente - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1>Cadastrar Paciente</h1>
        <p>Busque um usuário existente e complete o cadastro como paciente.</p>
    </header>

    <?php if (isset($erro)): ?>
        <div class="alert alert-danger"><?= e($erro ?? "") ?></div>
    <?php endif; ?>

    <!-- 1. SEARCH BLOCK -->
    <div class="mb-3">
        <form action="<?= BASE_URL ?>/usuario/procurar_paciente" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label" for="busca-nome">Buscar por Nome ou CPF:</label>
                    <input id="busca-nome" class="form-control" type="search" name="nome" placeholder="Digite o nome ou CPF..." autocomplete="off" value="<?= isset($_GET['nome']) ? e($_GET['nome']) : '' ?>">
                    <datalist id="lista-usuarios">
                        <?php if (!empty($todosOsUsuarios)): ?>
                            <?php foreach ($todosOsUsuarios as $todUnico): ?>
                                <option value="<?= e($todUnico['nome']) ?>"></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </datalist>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary btn-sm" type="submit">Buscar</button>
                </div>
            </div>
        </form>
    </div>

    <hr class="my-4">

    <!-- 2. CONDITIONAL PHP RENDERING FLOW -->
    <?php if (!empty($usuarios)): ?>

        <?php if (count($usuarios) > 1): ?>
            <!-- CASE A: More than one user found -->
            <div class="mb-3">
                <div class="alert alert-info">
                    Múltiplos usuários encontrados. Escolha qual deseja configurar:
                </div>
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Perfil</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?= e($u['nome']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><?= e($u['perfil']) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/usuario/procurar_paciente?nome=<?= urlencode($u['nome']) ?>" class="btn btn-primary btn-sm">
                                        Selecionar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- CASE B: Only one user returned -->
            <?php $u = $usuarios[0]; ?>

            <form action="<?= BASE_URL ?>/usuario/cadastrar_paciente" method="POST" enctype="multipart/form-data">
                <?= CSRF::getField() ?>

                <input type="hidden" name="controller" value="usuario">
                <input type="hidden" name="action" value="cadastrar_paciente">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nome:</label>
                            <input type="text" name="nome" value="<?= e($u['nome']) ?>" readonly class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Email:</label>
                            <input type="text" name="email" value="<?= e($u['email']) ?>" readonly class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Perfil Atual:</label>
                            <input type="text" value="<?= e($u['perfil']) ?>" readonly class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Foto do Paciente:</label>
                            <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Telefone de Emergência (legado):</label>
                            <input type="text" name="tel_emergencia" class="form-control campo-telefone" placeholder="Ex: Pai - (61) 9999-9999">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">CPF:</label>
                            <input type="text" name="cpf" class="form-control campo-cpf" required placeholder="000.000.000-00">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">RG:</label>
                            <input type="text" name="rg" class="form-control" placeholder="Identidade (opcional)">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Telefone Principal:</label>
                            <input type="text" name="telefone_principal" class="form-control campo-telefone" required placeholder="(61) 99999-9999">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Sexo:</label>
                            <select name="sexo" class="form-select">
                                <option value="">Selecione</option>
                                <option value="feminino">Feminino</option>
                                <option value="masculino">Masculino</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Estado Civil:</label>
                            <select name="estado_civil" class="form-select">
                                <option value="">Selecione</option>
                                <option value="solteiro">Solteiro(a)</option>
                                <option value="casado">Casado(a)</option>
                                <option value="divorciado">Divorciado(a)</option>
                                <option value="viuvo">Viuvo(a)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="mb-3">
                            <label class="form-label">Endereço - Rua:</label>
                            <input type="text" name="endereco_rua" class="form-control" placeholder="Rua, avenida...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Endereço - Número:</label>
                            <input type="text" name="endereco_numero" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Endereço - Complemento:</label>
                            <input type="text" name="endereco_complemento" class="form-control" placeholder="Apto, bloco, sala...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Endereço - CEP:</label>
                            <input type="text" name="endereco_cep" class="form-control campo-cep" placeholder="70000-000">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Endereço - Bairro:</label>
                            <input type="text" name="endereco_bairro" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Endereço - Cidade:</label>
                            <input type="text" name="endereco_cidade" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label class="form-label">Endereço - UF:</label>
                            <select name="endereco_uf" class="form-select">
                                <option value="">UF</option>
                                <option value="AC">AC</option>
                                <option value="AL">AL</option>
                                <option value="AP">AP</option>
                                <option value="AM">AM</option>
                                <option value="BA">BA</option>
                                <option value="CE">CE</option>
                                <option value="DF">DF</option>
                                <option value="ES">ES</option>
                                <option value="GO">GO</option>
                                <option value="MA">MA</option>
                                <option value="MT">MT</option>
                                <option value="MS">MS</option>
                                <option value="MG">MG</option>
                                <option value="PA">PA</option>
                                <option value="PB">PB</option>
                                <option value="PR">PR</option>
                                <option value="PE">PE</option>
                                <option value="PI">PI</option>
                                <option value="RJ">RJ</option>
                                <option value="RN">RN</option>
                                <option value="RS">RS</option>
                                <option value="RO">RO</option>
                                <option value="RR">RR</option>
                                <option value="SC">SC</option>
                                <option value="SP">SP</option>
                                <option value="SE">SE</option>
                                <option value="TO">TO</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Contato de Emergência - Nome:</label>
                            <input type="text" name="contato_nome" class="form-control" placeholder="Nome de quem avisar">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Contato de Emergência - Parentesco:</label>
                            <input type="text" name="contato_parentesco" class="form-control" placeholder="Pai, mãe, cônjuge...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Contato de Emergência - Telefone:</label>
                            <input type="text" name="contato_telefone" class="form-control campo-telefone" placeholder="(61) 99999-9999">
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Convênio - Operadora:</label>
                            <input type="text" name="convenio_operadora" class="form-control" placeholder="Ex.: Unimed, Amil...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Convênio - Número da Carteirinha:</label>
                            <input type="text" name="convenio_carteirinha" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Convênio - Validade:</label>
                            <input type="date" name="convenio_validade" class="form-control">
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Alergias:</label>
                            <textarea name="alergias" rows="2" class="form-control" placeholder="Quais alergias o paciente relata?"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Doenças Prévias:</label>
                            <textarea name="doencas_previas" rows="2" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Medicamentos em Uso:</label>
                            <textarea name="medicamentos_uso" rows="2" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Cirurgias:</label>
                            <textarea name="cirurgias" rows="2" class="form-control"></textarea>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="mb-3">
                    <label class="form-label">Data de Nascimento:</label>
                    <input type="date" name="data_nascimento" required class="form-control">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Finalizar Cadastro</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.href='<?= BASE_URL ?>/usuario/index'">Voltar</button>
                </div>
            </form>
        <?php endif; ?>

    <?php else: ?>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.href='<?= BASE_URL ?>/usuario/index'">Voltar</button>
        </div>
    <?php endif; ?>
</div>

<script src="<?= BASE_URL ?>/Assets/js/mascaras.js"></script>
<script src="<?= BASE_URL ?>/Assets/js/buscar_cep.js"></script>
</body>
</html>

<script src="<?= BASE_URL ?>/Assets/js/buscar_cpf.js"></script>
