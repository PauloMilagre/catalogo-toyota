<?php
/**
 * Estrutura comum a todas as páginas (antes repetida em cada .html).
 *
 * @var \Toyota\Core\View $this
 * @var string            $conteudo     HTML da página, já montado
 * @var ?array            $usuarioAtual
 */
$titulo ??= 'Catálogo de modelos';
$descricao ??= 'Catálogo de modelos Toyota com ficha técnica, preço e agendamento de test-drive.';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= $this->e($descricao) ?>">
  <title><?= $this->e($titulo) ?> | Toyota</title>
  <link rel="stylesheet" href="<?= $this->e($this->url('/css/estilo.css')) ?>">
  <link rel="icon" href="<?= $this->e($this->url('/img/logo.png')) ?>">
</head>
<body>

  <a class="salto" href="#conteudo">Pular para o conteúdo</a>

  <header class="topo">
    <a class="topo__marca" href="<?= $this->e($this->url('/')) ?>">
      <img src="<?= $this->e($this->url('/img/logo.png')) ?>" alt="Toyota — página inicial" width="110" height="72">
    </a>

    <nav class="topo__nav" aria-label="Navegação principal">
      <ul class="topo__lista">
        <li><a href="<?= $this->e($this->url('/')) ?>"<?= $this->atual('/') ?: $this->atual('/modelos') ?>>Catálogo</a></li>
        <li><a href="<?= $this->e($this->url('/agendar')) ?>"<?= $this->atual('/agendar') ?>>Agendar test-drive</a></li>
        <?php if ($usuarioAtual !== null): ?>
          <li><a href="<?= $this->e($this->url('/meus-agendamentos')) ?>"<?= $this->atual('/meus-agendamentos') ?>>Meus agendamentos</a></li>
          <li>
            <form class="topo__sair" method="post" action="<?= $this->e($this->url('/sair')) ?>">
              <?= $this->campoCsrf() ?>
              <button type="submit">Sair (<?= $this->e($usuarioAtual['nome']) ?>)</button>
            </form>
          </li>
        <?php else: ?>
          <li><a href="<?= $this->e($this->url('/entrar')) ?>"<?= $this->atual('/entrar') ?>>Entrar</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main id="conteudo" class="faixa">
    <?php foreach ($this->avisos() as $aviso): ?>
      <p class="alerta alerta--<?= $this->e($aviso['tipo']) ?>" role="status"><?= $this->e($aviso['mensagem']) ?></p>
    <?php endforeach; ?>

    <?= $conteudo ?>
  </main>

  <footer class="rodape">
    <p>Catálogo Toyota — Trabalhos Individuais I e II, SCOM 2026.</p>
    <p>Paulo Francisco da Silva</p>
  </footer>

</body>
</html>
