<?php

declare(strict_types=1);

namespace Toyota\Core;

/** Sessão em memória para os testes automatizados (sem cookie, sem arquivo). */
final class SessaoMemoria implements Sessao
{
    /** @var array<string, mixed> */
    private array $dados = [];

    public int $regeneracoes = 0;

    public function get(string $chave, mixed $padrao = null): mixed
    {
        return $this->dados[$chave] ?? $padrao;
    }

    public function set(string $chave, mixed $valor): void
    {
        $this->dados[$chave] = $valor;
    }

    public function remover(string $chave): void
    {
        unset($this->dados[$chave]);
    }

    public function regenerar(): void
    {
        $this->regeneracoes++;
    }

    public function destruir(): void
    {
        $this->dados = [];
    }
}
