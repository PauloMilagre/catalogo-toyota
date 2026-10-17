<?php
/**
 * Ficha do modelo (antes modelo.html, hilux.html e supra.html: três
 * arquivos quase iguais). Agora é um template só, preenchido pelo banco.
 *
 * @var \Toyota\Core\View $this
 * @var array             $modelo
 */
$paragrafos = array_filter(array_map('trim', explode("\n\n", (string) $modelo['descricao'])));
?>
<article class="ficha">

  <h1><?= $this->e($modelo['nome']) ?></h1>

  <?php if ($modelo['imagem']): ?>
    <figure class="ficha__foto">
      <img src="<?= $this->e($this->url('/img/' . $modelo['imagem'])) ?>"
           alt="<?= $this->e($modelo['imagem_alt']) ?>"
           width="<?= (int) $modelo['imagem_largura'] ?>" height="<?= (int) $modelo['imagem_altura'] ?>"
           fetchpriority="high">
      <figcaption><?= $this->e($modelo['legenda'] ?? $modelo['nome']) ?></figcaption>
    </figure>
  <?php endif; ?>

  <?php foreach ($paragrafos as $paragrafo): ?>
    <p class="ficha__texto"><?= $this->e($paragrafo) ?></p>
  <?php endforeach; ?>

  <aside class="tecnica" aria-labelledby="titulo-tecnica">
    <h2 id="titulo-tecnica">Ficha técnica</h2>
    <dl class="tecnica__lista">
      <dt>Preço a partir de</dt>
      <dd><?= $this->e($this->moeda($modelo['preco'])) ?></dd>

      <dt>Motorização</dt>
      <dd><?= $this->e($modelo['motor']) ?></dd>

      <dt>Potência</dt>
      <dd><?= (int) $modelo['potencia_cv'] ?> cv</dd>

      <dt>Câmbio</dt>
      <dd><?= $this->e($modelo['cambio']) ?></dd>

      <dt>Consumo urbano</dt>
      <dd><?= $this->e($this->numero($modelo['consumo_km_l'])) ?> km/l</dd>

      <?php if ($modelo['porta_malas_l'] !== null): ?>
        <dt>Porta-malas</dt>
        <dd><?= $this->e($this->numero($modelo['porta_malas_l'], 0)) ?> litros</dd>
      <?php endif; ?>

      <?php if ($modelo['carga_kg'] !== null): ?>
        <dt>Capacidade de carga</dt>
        <dd><?= $this->e($this->numero($modelo['carga_kg'], 0)) ?> kg</dd>
      <?php endif; ?>

      <dt>Garantia</dt>
      <dd>3 anos, sem limite de quilometragem</dd>
    </dl>
  </aside>

  <p>
    <a class="botao" href="<?= $this->e($this->url('/agendar', ['modelo' => $modelo['slug']])) ?>">
      Agendar test-drive: <?= $this->e($modelo['nome']) ?>
    </a>
  </p>

</article>
