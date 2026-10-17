<?php
/**
 * Mensagem de erro de um campo de formulário, ligada ao campo por id.
 *
 * @var \Toyota\Core\View $this
 * @var array             $erros
 * @var string            $campo
 */
if (!isset($erros[$campo])) {
    return;
}
?>
<span class="campo__erro" id="erro-<?= $this->e($campo) ?>"><?= $this->e($erros[$campo]) ?></span>
