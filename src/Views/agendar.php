<?php
/**
 * Formulário de agendamento (antes agendar.html, que não enviava para lugar
 * nenhum). Nome e e-mail saem da conta logada, então não são mais pedidos.
 *
 * @var \Toyota\Core\View $this
 * @var list<array>       $modelos
 * @var array             $antigo    valores já digitados
 * @var array             $erros     mensagem por campo
 * @var ?string           $mensagem  erro geral (ex.: horário lotado)
 * @var array             $usuarioAtual
 */
$valor = static fn (string $campo): string => is_scalar($antigo[$campo] ?? null) ? (string) $antigo[$campo] : '';
$descrito = function (string $campo, ?string $ajuda = null) use ($erros): string {
    $ids = array_filter([$ajuda, isset($erros[$campo]) ? 'erro-' . $campo : null]);
    $atributos = $ids === [] ? '' : ' aria-describedby="' . implode(' ', $ids) . '"';
    return $atributos . (isset($erros[$campo]) ? ' aria-invalid="true"' : '');
};
?>
<section class="abertura">
  <h1>Agendar test-drive</h1>
  <p class="abertura__texto">
    Escolha o modelo, a data e o período. A concessionária confirma o horário
    por e-mail em até um dia útil.
  </p>
</section>

<section class="formulario" aria-labelledby="titulo-formulario">
  <h2 id="titulo-formulario">Dados do agendamento</h2>

  <p>Agendando como <strong><?= $this->e($usuarioAtual['nome']) ?></strong> (<?= $this->e($usuarioAtual['email']) ?>).</p>

  <?php if ($mensagem !== null): ?>
    <p class="alerta alerta--erro" role="alert"><?= $this->e($mensagem) ?></p>
  <?php endif; ?>

  <!-- novalidate: a validação que vale é a do servidor, que sempre roda.
       Os atributos required/min/max continuam aqui para orientar o usuário. -->
  <form method="post" action="<?= $this->e($this->url('/agendar')) ?>" novalidate>
    <?= $this->campoCsrf() ?>

    <div class="campo">
      <label for="modelo_id">Modelo de interesse</label>
      <select id="modelo_id" name="modelo_id" required<?= $descrito('modelo_id') ?>>
        <option value="">Selecione um modelo</option>
        <?php foreach ($modelos as $modelo): ?>
          <option value="<?= (int) $modelo['id'] ?>"<?= $valor('modelo_id') === (string) $modelo['id'] ? ' selected' : '' ?>>
            <?= $this->e($modelo['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?= $this->parcial('campo-erro', ['erros' => $erros, 'campo' => 'modelo_id']) ?>
    </div>

    <div class="campo">
      <label for="data">Data preferida</label>
      <input type="date" id="data" name="data" required
             min="<?= $this->e($dataMinima) ?>" max="<?= $this->e($dataMaxima) ?>"
             value="<?= $this->e($valor('data')) ?>"<?= $descrito('data', 'ajuda-data') ?>>
      <span class="campo__ajuda" id="ajuda-data">De amanhã até 60 dias à frente. Fechado aos domingos.</span>
      <?= $this->parcial('campo-erro', ['erros' => $erros, 'campo' => 'data']) ?>
    </div>

    <div class="campo">
      <label for="periodo">Período</label>
      <select id="periodo" name="periodo" required<?= $descrito('periodo') ?>>
        <option value="">Selecione o período</option>
        <option value="manha"<?= $valor('periodo') === 'manha' ? ' selected' : '' ?>>Manhã, das 8h às 12h</option>
        <option value="tarde"<?= $valor('periodo') === 'tarde' ? ' selected' : '' ?>>Tarde, das 13h às 18h</option>
      </select>
      <?= $this->parcial('campo-erro', ['erros' => $erros, 'campo' => 'periodo']) ?>
    </div>

    <div class="campo">
      <label for="telefone">Telefone</label>
      <input type="tel" id="telefone" name="telefone" autocomplete="tel" maxlength="20"
             value="<?= $this->e($valor('telefone')) ?>"<?= $descrito('telefone', 'ajuda-telefone') ?>>
      <span class="campo__ajuda" id="ajuda-telefone">Opcional. Usado apenas se não conseguirmos contato por e-mail.</span>
      <?= $this->parcial('campo-erro', ['erros' => $erros, 'campo' => 'telefone']) ?>
    </div>

    <div class="campo">
      <label for="observacoes">Observações</label>
      <textarea id="observacoes" name="observacoes" maxlength="400"<?= $descrito('observacoes', 'ajuda-observacoes') ?>><?= $this->e($valor('observacoes')) ?></textarea>
      <span class="campo__ajuda" id="ajuda-observacoes">
        Até 400 caracteres. Conte se quer testar em estrada, avaliar seu usado ou simular financiamento.
      </span>
      <?= $this->parcial('campo-erro', ['erros' => $erros, 'campo' => 'observacoes']) ?>
    </div>

    <button type="submit" class="botao">Enviar agendamento</button>
  </form>
</section>
