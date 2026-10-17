<?php

declare(strict_types=1);

namespace Toyota\Core;

/**
 * Mensagens de uma página para a próxima ("Agendamento criado.").
 * Ficam na sessão até a próxima página HTML ser exibida.
 */
final class Avisos
{
    private const CHAVE = '_avisos';

    public function __construct(private Sessao $sessao)
    {
    }

    public function sucesso(string $mensagem): void
    {
        $this->adicionar('sucesso', $mensagem);
    }

    public function erro(string $mensagem): void
    {
        $this->adicionar('erro', $mensagem);
    }

    /** @return list<array{tipo: string, mensagem: string}> */
    public function consumir(): array
    {
        $avisos = $this->sessao->get(self::CHAVE, []);
        $this->sessao->remover(self::CHAVE);
        return is_array($avisos) ? $avisos : [];
    }

    private function adicionar(string $tipo, string $mensagem): void
    {
        $avisos = $this->sessao->get(self::CHAVE, []);
        $avisos[] = ['tipo' => $tipo, 'mensagem' => $mensagem];
        $this->sessao->set(self::CHAVE, $avisos);
    }
}
