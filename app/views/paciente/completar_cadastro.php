<?php
Session::start();
if (!function_exists('fCpfPac')) {
    function fCpfPac($v) { $v = preg_replace('/\D/', '', $v ?? ''); return $v ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $v) : ''; }
    function fTelPac($v) { $v = preg_replace('/\D/', '', $v ?? ''); return $v ? preg_replace('/(\d{2})(\d{4,5})(\d{4})/', '($1) $2-$3', $v) : ''; }
    function fCepPac($v) { $v = preg_replace('/\D/', '', $v ?? ''); return $v ? preg_replace('/(\d{5})(\d{3})/', '$1-$2', $v) : ''; }
}
$dados = $dados ?? [];
$selecionado = fn($campo, $valor) => (($dados[$campo] ?? '') === $valor) ? 'selected' : '';
$avatarPadrao = "data:image/svg+xml;charset=utf-8," . rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' width='110' height='110' viewBox='0 0 110 110'><rect width='110' height='110' fill='#eef2ff'/><circle cx='55' cy='44' r='22' fill='#4f46e5'/><path d='M13 100c4-21 21-30 42-30s38 9 42 30' fill='#4f46e5'/></svg>");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Cadastro - EloMed</title>
</head>
<body>
<?php include __DIR__ . '/../header.php'; ?>

<div class="main-container">
    <header class="page-header">
        <h1><i class="bi bi-person-vcard"></i> Completar Cadastro</h1>
        <p>Preencha seus dados para ativar seu perfil de paciente na EloMed.</p>
    </header>

    <form action="<?= BASE_URL ?>/paciente/salvar_completar_cadastro" method="POST"
          enctype="multipart/form-data" class="mt-3" novalidate>
        <?= CSRF::getField() ?>

        <div class="row g-3">
            <!-- PHOTO -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-camera me-1 text-primary"></i> Foto do Perfil</div>
                    <div class="card-body d-flex align-items-center gap-4 flex-wrap">
                        <div class="text-center">
                            <img id="previewFoto" src="<?= !empty($foto_atual) ? BASE_URL . '/' . e($foto_atual) : $avatarPadrao ?>"
                                 alt="Foto do paciente" class="avatar-photo"
                                 style="width:110px;height:110px;object-fit:cover;"
                                 onerror="this.src='<?= $avatarPadrao ?>'">
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold">Adicionar/Atualizar foto (JPG, PNG ou WEBP, máx. 2MB)</label>
                            <input type="file" name="foto" id="inputFoto" class="form-control"
                                   accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Deixe vazio para manter a foto atual.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PERSONAL DATA -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-person me-1 text-primary"></i> Dados Pessoais</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">CPF *</label>
                                <input type="text" name="cpf" class="form-control campo-cpf" required
                                       placeholder="000.000.000-00" value="<?= e(fCpfPac($dados['cpf'] ?? '')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">RG</label>
                                <input type="text" name="rg" class="form-control" placeholder="Identidade (opcional)"
                                       value="<?= e($dados['rg'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data de Nascimento *</label>
                                <input type="date" name="data_nascimento" class="form-control" required
                                       value="<?= e($dados['data_nascimento'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sexo</label>
                                <select name="sexo" class="form-select">
                                    <option value="">Selecione</option>
                                    <option value="feminino" <?= $selecionado('sexo', 'feminino') ?>>Feminino</option>
                                    <option value="masculino" <?= $selecionado('sexo', 'masculino') ?>>Masculino</option>
                                    <option value="outro" <?= $selecionado('sexo', 'outro') ?>>Outro</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado Civil</label>
                                <select name="estado_civil" class="form-select">
                                    <option value="">Selecione</option>
                                    <option value="solteiro" <?= $selecionado('estado_civil', 'solteiro') ?>>Solteiro(a)</option>
                                    <option value="casado" <?= $selecionado('estado_civil', 'casado') ?>>Casado(a)</option>
                                    <option value="divorciado" <?= $selecionado('estado_civil', 'divorciado') ?>>Divorciado(a)</option>
                                    <option value="viuvo" <?= $selecionado('estado_civil', 'viuvo') ?>>Viúvo(a)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Telefone Principal *</label>
                                <input type="text" name="telefone_principal" class="form-control campo-telefone" required
                                       placeholder="(61) 99999-9999" value="<?= e(fTelPac($dados['telefone_principal'] ?? '')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contato de Emergência</label>
                                <input type="text" name="tel_emergencia" class="form-control campo-telefone"
                                       placeholder="Ex: Pai - (61) 99999-9999" value="<?= e(fTelPac($dados['contato_emergencia'] ?? '')) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADDRESS -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-geo-alt me-1 text-primary"></i> Endereço</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Rua / Avenida</label>
                                <input type="text" name="endereco_rua" class="form-control"
                                       value="<?= e($dados['endereco_rua'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Número</label>
                                <input type="text" name="endereco_numero" class="form-control"
                                       value="<?= e($dados['endereco_numero'] ?? '') ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Complemento</label>
                                <input type="text" name="endereco_complemento" class="form-control"
                                       placeholder="Apto, bloco, sala..." value="<?= e($dados['endereco_complemento'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CEP</label>
                                <input type="text" name="endereco_cep" class="form-control campo-cep"
                                       placeholder="70000-000" value="<?= e(fCepPac($dados['endereco_cep'] ?? '')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="endereco_bairro" class="form-control"
                                       value="<?= e($dados['endereco_bairro'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cidade</label>
                                <input type="text" name="endereco_cidade" class="form-control"
                                       value="<?= e($dados['endereco_cidade'] ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">UF</label>
                                <select name="endereco_uf" class="form-select">
                                    <option value="">UF</option>
                                    <option value="AC" <?= $selecionado('endereco_uf', 'AC') ?>>AC</option>
                                    <option value="AL" <?= $selecionado('endereco_uf', 'AL') ?>>AL</option>
                                    <option value="AP" <?= $selecionado('endereco_uf', 'AP') ?>>AP</option>
                                    <option value="AM" <?= $selecionado('endereco_uf', 'AM') ?>>AM</option>
                                    <option value="BA" <?= $selecionado('endereco_uf', 'BA') ?>>BA</option>
                                    <option value="CE" <?= $selecionado('endereco_uf', 'CE') ?>>CE</option>
                                    <option value="DF" <?= $selecionado('endereco_uf', 'DF') ?>>DF</option>
                                    <option value="ES" <?= $selecionado('endereco_uf', 'ES') ?>>ES</option>
                                    <option value="GO" <?= $selecionado('endereco_uf', 'GO') ?>>GO</option>
                                    <option value="MA" <?= $selecionado('endereco_uf', 'MA') ?>>MA</option>
                                    <option value="MT" <?= $selecionado('endereco_uf', 'MT') ?>>MT</option>
                                    <option value="MS" <?= $selecionado('endereco_uf', 'MS') ?>>MS</option>
                                    <option value="MG" <?= $selecionado('endereco_uf', 'MG') ?>>MG</option>
                                    <option value="PA" <?= $selecionado('endereco_uf', 'PA') ?>>PA</option>
                                    <option value="PB" <?= $selecionado('endereco_uf', 'PB') ?>>PB</option>
                                    <option value="PR" <?= $selecionado('endereco_uf', 'PR') ?>>PR</option>
                                    <option value="PE" <?= $selecionado('endereco_uf', 'PE') ?>>PE</option>
                                    <option value="PI" <?= $selecionado('endereco_uf', 'PI') ?>>PI</option>
                                    <option value="RJ" <?= $selecionado('endereco_uf', 'RJ') ?>>RJ</option>
                                    <option value="RN" <?= $selecionado('endereco_uf', 'RN') ?>>RN</option>
                                    <option value="RS" <?= $selecionado('endereco_uf', 'RS') ?>>RS</option>
                                    <option value="RO" <?= $selecionado('endereco_uf', 'RO') ?>>RO</option>
                                    <option value="RR" <?= $selecionado('endereco_uf', 'RR') ?>>RR</option>
                                    <option value="SC" <?= $selecionado('endereco_uf', 'SC') ?>>SC</option>
                                    <option value="SP" <?= $selecionado('endereco_uf', 'SP') ?>>SP</option>
                                    <option value="SE" <?= $selecionado('endereco_uf', 'SE') ?>>SE</option>
                                    <option value="TO" <?= $selecionado('endereco_uf', 'TO') ?>>TO</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMERGENCY CONTACT -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-telephone me-1 text-primary"></i> Contato de Emergência</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nome</label>
                                <input type="text" name="contato_nome" class="form-control"
                                       placeholder="Nome de quem avisar" value="<?= e($dados['contato_nome'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Parentesco</label>
                                <input type="text" name="contato_parentesco" class="form-control"
                                       placeholder="Pai, mãe, cônjuge..." value="<?= e($dados['contato_parentesco'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Telefone</label>
                                <input type="text" name="contato_telefone" class="form-control campo-telefone"
                                       placeholder="(61) 99999-9999" value="<?= e(fTelPac($dados['contato_telefone'] ?? '')) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HEALTH PLAN -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-shield-check me-1 text-primary"></i> Convênio (opcional)</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Operadora</label>
                                <input type="text" name="convenio_operadora" class="form-control"
                                       placeholder="Ex.: Unimed, Amil..." value="<?= e($dados['convenio_operadora'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Número da Carteirinha</label>
                                <input type="text" name="convenio_carteirinha" class="form-control"
                                       value="<?= e($dados['convenio_carteirinha'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Validade</label>
                                <input type="date" name="convenio_validade" class="form-control"
                                       value="<?= e($dados['convenio_validade'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MEDICAL HISTORY -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-clipboard2-pulse me-1 text-primary"></i> Histórico de Saúde (opcional)</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Alergias</label>
                                <textarea name="alergias" rows="2" class="form-control" placeholder="Quais alergias você tem?"><?= e($dados['alergias'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Doenças Prévias</label>
                                <textarea name="doencas_previas" rows="2" class="form-control"><?= e($dados['doencas_previas'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Medicamentos em Uso</label>
                                <textarea name="medicamentos_uso" rows="2" class="form-control"><?= e($dados['medicamentos_uso'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cirurgias</label>
                                <textarea name="cirurgias" rows="2" class="form-control"><?= e($dados['cirurgias'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-check-lg me-1"></i> Salvar Cadastro
            </button>
            <a href="<?= BASE_URL ?>/paciente/index" class="btn btn-outline-secondary rounded-pill px-4">Voltar ao Painel</a>
        </div>
    </form>
</div>

<script src="<?= BASE_URL ?>/Assets/js/mascaras.js"></script>
<script src="<?= BASE_URL ?>/Assets/js/buscar_cep.js"></script>
<script>
document.getElementById('inputFoto').addEventListener('change', function () {
    const arquivo = this.files && this.files[0];
    if (!arquivo) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(arquivo.type)) {
        alert('Formato inválido. Use JPG, PNG ou WEBP.');
        this.value = '';
        return;
    }
    if (arquivo.size > 2 * 1024 * 1024) {
        alert('A foto deve ter no máximo 2MB.');
        this.value = '';
        return;
    }
    const leitor = new FileReader();
    leitor.onload = function (e) {
        document.getElementById('previewFoto').src = e.target.result;
    };
    leitor.readAsDataURL(arquivo);
});
</script>
</body>
</html>

<script src="<?= BASE_URL ?>/Assets/js/buscar_cpf.js"></script>
