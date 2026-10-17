<?php
/**
 * Catálogo (antes index.html). Os cards agora vêm da tabela modelos.
 *
 * @var \Toyota\Core\View $this
 * @var list<array>       $modelos
 */
?>
<section class="abertura">
  <h1>Catálogo de modelos</h1>
  <p class="abertura__texto">
    Três modelos, os dados que importam e um caminho direto para o test-drive.
    Sem vídeo, sem carrossel, sem espera.
  </p>
</section>

<section class="catalogo" aria-labelledby="titulo-catalogo">
  <h2 id="titulo-catalogo">Modelos disponíveis</h2>

  <?php if ($modelos === []): ?>
    <p>Nenhum modelo disponível no momento.</p>
  <?php endif; ?>

  <div class="grade">
    <?php foreach ($modelos as $i => $modelo): ?>
      <article class="card">
        <figure class="card__foto">
          <?php if ($modelo['imagem']): ?>
            <!-- A primeira imagem é a maior da tela: carrega com prioridade.
                 As demais só quando estiverem para aparecer (lazy). -->
            <img src="<?= $this->e($this->url('/img/' . $modelo['imagem'])) ?>"
                 alt="<?= $this->e($modelo['imagem_alt']) ?>"
                 width="<?= (int) $modelo['imagem_largura'] ?>" height="<?= (int) $modelo['imagem_altura'] ?>"
                 <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
          <?php endif; ?>
          <figcaption><?= $this->e($modelo['categoria']) ?></figcaption>
        </figure>
        <h3 class="card__nome"><?= $this->e($modelo['nome']) ?></h3>
        <dl class="card__dados">
          <dt>Preço a partir de</dt>
          <dd><?= $this->e($this->moeda($modelo['preco'])) ?></dd>
          <dt>Potência</dt>
          <dd><?= (int) $modelo['potencia_cv'] ?> cv</dd>
          <dt>Consumo médio</dt>
          <dd><?= $this->e($this->numero($modelo['consumo_km_l'])) ?> km/l</dd>
        </dl>
        <a class="card__link" href="<?= $this->e($this->url('/modelos/' . $modelo['slug'])) ?>">
          Ver ficha completa: <?= $this->e($modelo['nome']) ?>
        </a>
      </article>
    <?php endforeach; ?>
  </div>
</section>
