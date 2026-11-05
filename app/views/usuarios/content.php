<div class="table-container">
    <h2><i class="bi bi-people text-primary"></i> Controle de Usuários</h2>

    <?php if (isset($usuarioEncontrado) && !empty($usuarioEncontrado)): ?>
        <div class="alert alert-success">
            <strong>Filtro ativo:</strong> Exibindo resultados encontrados na busca.
        </div>
    <?php endif; ?>

    <?php
        $categorias = [
            'medico'             => ['Médicos',               'bi-heart-pulse', 'badge-primary'],
            'enfermeiro'         => ['Enfermeiros',           'bi-person-fill-check', 'badge-warning'],
            'tecnico_enfermagem' => ['Técnicos de Enfermagem','bi-clipboard2-pulse', 'badge-secondary'],
            'admin'              => ['Administradores',       'bi-shield-fill-check', 'badge-primary'],
            'paciente'           => ['Pacientes',             'bi-person-heart', 'badge-info'],
        ];

        $agrupados = [];
        foreach ($usuarios as $u) {
            $p = mb_strtolower($u['perfil'] ?? '');
            if (!isset($agrupados[$p])) $agrupados[$p] = [];
            $agrupados[$p][] = $u;
        }

        $ordem = ['medico', 'enfermeiro', 'tecnico_enfermagem', 'admin', 'paciente'];
        uksort($agrupados, function ($a, $b) use ($ordem) {
            $ia = array_search($a, $ordem, true);
            $ib = array_search($b, $ordem, true);
            if ($ia === false) $ia = count($ordem);
            if ($ib === false) $ib = count($ordem);
            return $ia <=> $ib;
        });
    ?>

    <?php if (empty($usuarios)): ?>
        <div class="sem-dados">
            <i class="bi bi-inbox"></i> Nenhum registro encontrado no sistema.
        </div>
    <?php else: ?>
        <?php foreach ($agrupados as $perfilKey => $lista): ?>
            <?php
                $catMeta = $categorias[$perfilKey] ?? [ucfirst($perfilKey), 'bi-person', 'badge-default'];
                $lista = array_values(array_filter($lista, fn($x) => !empty($x['nome'])));
                usort($lista, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));
            ?>
            <h4 class="mt-4 mb-2 d-flex align-items-center gap-2">
                <i class="bi <?= $catMeta[1] ?> text-primary"></i> <?= $catMeta[0] ?>
                <span class="badge <?= $catMeta[2] ?> rounded-pill"><?= count($lista) ?></span>
            </h4>
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <tbody>
                        <?php foreach ($lista as $user): ?>
                            <?php $userStatus = $user['status'] ?? 'ativo'; ?>
                            <tr class="<?= $userStatus !== 'ativo' ? 'table-light opacity-50' : '' ?>">
                                <td style="width: 80px;"><strong>#<?= (int)$user['id'] ?></strong></td>
                                <td>
                                    <strong><?= e($user['nome']) ?></strong><br>
                                    <small class="text-muted"><?= e($user['email']) ?></small>
                                </td>
                                <td style="width: 90px;">
                                    <?php if ($userStatus === 'ativo'): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Desativado</span>
                                    <?php endif; ?>
                                </td>
                                <td style="width: 220px;">
                                    <div class="d-flex gap-2 align-items-center justify-content-end">
                                        <?php if ($userStatus === 'ativo'): ?>
                                            <a href="<?= BASE_URL ?>/usuario/editar/<?= (int)$user['id'] ?>" class="btn btn-outline-primary btn-sm">Editar</a>
                                            <form method="POST" action="<?= BASE_URL ?>/usuario/excluir/<?= (int)$user['id'] ?>"
                                                  onsubmit="return confirm('Deseja realmente desativar este usuário?');">
                                                <?= CSRF::getField() ?>
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-person-x me-1"></i> Desativar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="<?= BASE_URL ?>/usuario/reativar/<?= (int)$user['id'] ?>">
                                                <?= CSRF::getField() ?>
                                                <button type="submit" class="btn btn-outline-success btn-sm">
                                                    <i class="bi bi-arrow-repeat me-1"></i> Reativar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
