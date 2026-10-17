<?php

declare(strict_types=1);

namespace Toyota\Repositories;

use PDO;
use PDOStatement;

/**
 * Base da camada de acesso a dados. Só as classes desta pasta escrevem SQL.
 *
 * Toda consulta passa por executar(), que usa prepared statements: o SQL
 * tem marcadores (:email) e os valores vão separados, com o tipo certo.
 * Nenhum valor vindo do usuário é colado no texto do SQL.
 */
abstract class Repositorio
{
    public function __construct(protected PDO $pdo)
    {
    }

    /** @param array<string, mixed> $parametros */
    protected function executar(string $sql, array $parametros = []): PDOStatement
    {
        $comando = $this->pdo->prepare($sql);
        foreach ($parametros as $nome => $valor) {
            $tipo = match (true) {
                is_int($valor)  => PDO::PARAM_INT,
                is_bool($valor) => PDO::PARAM_BOOL,
                $valor === null => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $comando->bindValue(':' . $nome, is_bool($valor) ? (int) $valor : $valor, $tipo);
        }
        $comando->execute();
        return $comando;
    }

    /** @param array<string, mixed> $parametros */
    protected function linha(string $sql, array $parametros = []): ?array
    {
        $linha = $this->executar($sql, $parametros)->fetch();
        return $linha === false ? null : $linha;
    }

    /** @param array<string, mixed> $parametros */
    protected function valor(string $sql, array $parametros = []): mixed
    {
        return $this->executar($sql, $parametros)->fetchColumn();
    }

    protected function ultimoId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }
}
