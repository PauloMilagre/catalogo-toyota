<?php

declare(strict_types=1);

namespace Toyota\Core;

use PDO;
use Throwable;

/**
 * Executa um bloco dentro de uma transação do banco.
 * Se qualquer coisa falhar no meio, nada do que foi feito fica gravado.
 */
final class Transacao
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @template T
     * @param callable(): T $bloco
     * @return T
     */
    public function executar(callable $bloco): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $resultado = $bloco();
            $this->pdo->commit();
            return $resultado;
        } catch (Throwable $erro) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $erro;
        }
    }
}
