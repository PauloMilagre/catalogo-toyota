<?php

declare(strict_types=1);

namespace Toyota\Core;

/** Dados de entrada inválidos: HTTP 422 com a mensagem de cada campo. */
final class ValidacaoException extends HttpException
{
    /** @param array<string, string> $campos */
    public function __construct(array $campos, string $mensagem = 'Os dados enviados são inválidos.')
    {
        parent::__construct(422, $mensagem, $campos);
    }
}
