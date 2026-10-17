<?php
/**
 * Links de página anterior/próxima, reaproveitados nas listagens.
 *
 * @var \Toyota\Core\View $this
 * @var array             $meta       pagina, total_paginas, total
 * @var string            $caminho
 * @var array             $filtros    parâmetros extras da URL (ex.: status)
 */
$filtros ??= [];
if ($meta['total_paginas'] <= 1) {
    return;
}
?>
<nav class="paginacao" aria-label="Paginação">
  <?php if ($meta['pagina'] > 1): ?>
    <a href="<?= $this->e($this->url($caminho, $filtros + ['pagina' => $meta['pagina'] - 1])) ?>">← Anterior</a>
  <?php endif; ?>
  <span>Página <?= (int) $meta['pagina'] ?> de <?= (int) $meta['total_paginas'] ?></span>
  <?php if ($meta['pagina'] < $meta['total_paginas']): ?>
    <a href="<?= $this->e($this->url($caminho, $filtros + ['pagina' => $meta['pagina'] + 1])) ?>">Próxima →</a>
  <?php endif; ?>
</nav>
