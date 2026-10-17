<?php

declare(strict_types=1);

namespace Toyota\Core;

use RuntimeException;
use Throwable;

/**
 * Erro com código HTTP e mensagem que pode ser mostrada ao usuário.
 * Qualquer camada pode lançar; o ErrorHandler transforma em resposta.
 */
class HttpException extends RuntimeException
{
    /**
     * @param array<string, string> $campos     erros por campo (validação)
     * @param array<string, string> $cabecalhos cabeçalhos extras (ex.: Retry-After)
     */
    public function __construct(
        public readonly int $status,
        string $mensagem,
        public readonly array $campos = [],
        public readonly array $cabecalhos = [],
        ?Throwable $anterior = null
    ) {
        parent::__construct($mensagem, $status, $anterior);
    }

    public static function naoEncontrado(string $mensagem = 'Recurso não encontrado.'): self
    {
        return new self(404, $mensagem);
    }

    public static function proibido(string $mensagem = 'Seu perfil não tem permissão para acessar este recurso.'): self
    {
        return new self(403, $mensagem);
    }

    public static function conflito(string $mensagem): self
    {
        return new self(409, $mensagem);
    }
}
