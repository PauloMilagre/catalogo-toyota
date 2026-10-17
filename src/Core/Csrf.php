<?php

declare(strict_types=1);

namespace Toyota\Core;

/**
 * Proteção contra CSRF (Cross-Site Request Forgery).
 *
 * Cada sessão recebe um token aleatório. Todo formulário que altera dados
 * leva esse token num campo oculto (_csrf), e a API recebe no cabeçalho
 * X-CSRF-Token. Um site malicioso consegue fazer o navegador enviar o
 * cookie da sessão, mas não consegue ler o token, então o pedido falso
 * chega sem ele e é recusado com 403.
 */
final class Csrf
{
    private const CHAVE = '_csrf';

    public function __construct(private Sessao $sessao)
    {
    }

    public function token(): string
    {
        $token = $this->sessao->get(self::CHAVE);
        if (!is_string($token) || $token === '') {
            $token = $this->renovar();
        }
        return $token;
    }

    /** Gera um token novo. Chamado no login, junto com a troca do id da sessão. */
    public function renovar(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->sessao->set(self::CHAVE, $token);
        return $token;
    }

    /** @throws HttpException 403 se o token estiver ausente ou não conferir */
    public function validar(mixed $enviado): void
    {
        $esperado = $this->sessao->get(self::CHAVE);
        $valido = is_string($esperado) && $esperado !== ''
            && is_string($enviado) && hash_equals($esperado, $enviado);

        if (!$valido) {
            throw new HttpException(403, 'Token CSRF ausente ou inválido. Recarregue a página e tente de novo.');
        }
    }
}
