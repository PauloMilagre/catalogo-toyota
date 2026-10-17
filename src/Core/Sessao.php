<?php

declare(strict_types=1);

namespace Toyota\Core;

/**
 * Armazenamento da sessão do usuário.
 * Em produção: SessaoNativa (cookie + $_SESSION). Nos testes: SessaoMemoria.
 */
interface Sessao
{
    public function get(string $chave, mixed $padrao = null): mixed;

    public function set(string $chave, mixed $valor): void;

    public function remover(string $chave): void;

    /** Troca o identificador da sessão (feito a cada login). */
    public function regenerar(): void;

    /** Apaga todos os dados e invalida o identificador atual. */
    public function destruir(): void;
}
