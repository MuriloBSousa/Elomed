<?php
/**
 * Componente de paginação reutilizável.
 *
 * Renderizado por Pagination::render(), que disponibiliza $data
 * (retorno de Pagination::toArray()) e $pagination (o próprio objeto).
 */
$paginas = $data['pages'] ?? [];
$atual   = (int) ($data['current_page'] ?? 1);
$total   = (int) ($data['total_pages'] ?? 1);
?>
<nav class="pagination-nav" aria-label="Paginação">
    <?php if ($data['has_previous']): ?>
        <a class="btn btn-outline-primary btn-sm" href="<?= e($data['previous_url']) ?>" rel="prev">
            <i class="bi bi-chevron-left"></i> Anterior
        </a>
    <?php else: ?>
        <button class="btn btn-outline-primary btn-sm" type="button" disabled>
            <i class="bi bi-chevron-left"></i> Anterior
        </button>
    <?php endif; ?>

    <?php foreach ($paginas as $pagina): ?>
        <?php if ($pagina === $atual): ?>
            <span class="btn btn-primary btn-sm" aria-current="page"><?= (int) $pagina ?></span>
        <?php else: ?>
            <a class="btn btn-outline-primary btn-sm" href="<?= e($pagination->getUrl($pagina)) ?>">
                <?= (int) $pagina ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($data['has_next']): ?>
        <a class="btn btn-outline-primary btn-sm" href="<?= e($data['next_url']) ?>" rel="next">
            Próxima <i class="bi bi-chevron-right"></i>
        </a>
    <?php else: ?>
        <button class="btn btn-outline-primary btn-sm" type="button" disabled>
            Próxima <i class="bi bi-chevron-right"></i>
        </button>
    <?php endif; ?>
</nav>
<p class="pagination-summary">
    Página <?= $atual ?> de <?= $total ?>
    (<?= (int) $data['from'] ?>–<?= (int) $data['to'] ?> de <?= (int) $data['total'] ?> registros)
</p>
