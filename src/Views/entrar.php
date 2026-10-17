<?php
/**
 * @var \Toyota\Core\View $this
 * @var array             $erros
 * @var ?string           $mensagem
 * @var string            $email
 * @var string            $voltar
 */
?>
<section class="formulario" aria-labelledby="titulo-entrar">
  <h1 id="titulo-entrar">Entrar</h1>

  <?php if ($mensagem !== null && $erros === []): ?>
    <p class="alerta alerta--erro" role="alert"><?= $this->e($mensagem) ?></p>
  <?php endif; ?>

  <form method="post" action="<?= $this->e($this->url('/entrar')) ?>" novalidate>
    <?= $this->campoCsrf() ?>
    <?php if ($voltar !== ''): ?>
      <input type="hidden" name="voltar" value="<?= $this->e($voltar) ?>">
    <?php endif; ?>

    <div class="campo">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" required autocomplete="email" maxlength="150"
             value="<?= $this->e($email) ?>"<?= isset($erros['email']) ? ' aria-invalid="true" aria-describedby="erro-email"' : '' ?>>
      <?= $this->parcial('campo-erro', ['erros' => $erros, 'campo' => 'email']) ?>
    </div>

    <div class="campo">
      <label for="senha">Senha</label>
      <input type="password" id="senha" name="senha" required autocomplete="current-password" maxlength="72"
             <?= isset($erros['senha']) ? 'aria-invalid="true" aria-describedby="erro-senha"' : '' ?>>
      <?= $this->parcial('campo-erro', ['erros' => $erros, 'campo' => 'senha']) ?>
    </div>

    <button type="submit" class="botao">Entrar</button>
  </form>

  <p class="formulario__rodape">Ainda não tem conta? <a href="<?= $this->e($this->url('/cadastro')) ?>">Crie a sua</a>.</p>
</section>
