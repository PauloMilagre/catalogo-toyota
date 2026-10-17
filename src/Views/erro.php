<?php
/**
 * Página de erro padronizada (404, 403, 405, 500...).
 *
 * @var \Toyota\Core\View $this
 * @var int               $status
 * @var string            $mensagem
 * @var ?array            $detalhes  só em modo de desenvolvimento
 */
?>
<section class="pagina" aria-labelledby="titulo-erro">
  <h1 id="titulo-erro">Erro <?= (int) $status ?></h1>
  <p><?= $this->e($mensagem) ?></p>
  <p><a href="<?= $this->e($this->url('/')) ?>">Voltar ao catálogo</a></p>

  <?php if ($detalhes !== null): ?>
    <h2>Detalhes técnicos (modo de desenvolvimento)</h2>
    <pre class="erro-detalhes"><?= $this->e(json_encode($detalhes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
  <?php endif; ?>
</section>
