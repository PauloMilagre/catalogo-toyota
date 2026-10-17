<?php
/**
 * @var \Toyota\Core\View $this
 * @var array             $erros
 * @var array             $antigo
 * @var ?string           $mensagem
 */
$mensagem ??= null;
$valor = static fn (string $campo): string => is_string($antigo[$campo] ?? null) ? $antigo[$campo] : '';
?>
<section class="formulario" aria-labelledby="titulo-cadastro">
  <h1 id="titulo-cadastro">Criar conta</h1>
  <p>Com a conta você agenda test-drives e acompanha a confirmação.</p>

  <?php if ($mensagem !== null): ?>
    <p class="alerta alerta--erro" role="alert"><?= $this->e($mensagem) ?></p>
  <?php endif; ?>

  <form method="post" action="<?= $this->e($this->url('/cadastro')) ?>" novalidate>
    <?= $this->campoCsrf() ?>

    <div class="campo">
      <label for="nome">Nome completo</label>
      <input type="text" id="nome" name="nome" required minlength="3" maxlength="100" autocomplete="name"
             value="<?= $this->e($valor('nome')) ?>"<?= isset($erros['nome']) ? ' aria-invalid="true" aria-describedby="erro-nome"' : '' ?>>
      <?= $this->parcial('campo-erro', ['erros' => $erros, 'campo' => 'nome']) ?>
    </div>

    <div class="campo">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" required maxlength="150" autocomplete="email"
             value="<?= $this->e($valor('email')) ?>"<?= isset($erros['email']) ? ' aria-invalid="true" aria-describedby="erro-email"' : '' ?>>
      <?= $this->parcial('campo-erro', ['erros' => $erros, 'campo' => 'email']) ?>
    </div>

    <div class="campo">
      <label for="senha">Senha</label>
      <input type="password" id="senha" name="senha" required minlength="8" maxlength="72" autocomplete="new-password"
             aria-describedby="ajuda-senha<?= isset($erros['senha']) ? ' erro-senha' : '' ?>"<?= isset($erros['senha']) ? ' aria-invalid="true"' : '' ?>>
      <span class="campo__ajuda" id="ajuda-senha">De 8 a 72 caracteres, com letras e números.</span>
      <?= $this->parcial('campo-erro', ['erros' => $erros, 'campo' => 'senha']) ?>
    </div>

    <button type="submit" class="botao">Criar conta</button>
  </form>

  <p class="formulario__rodape">Já tem conta? <a href="<?= $this->e($this->url('/entrar')) ?>">Entrar</a>.</p>
</section>
