<?php

declare(strict_types=1);

namespace Toyota\Core;

use InvalidArgumentException;
use PDO;

/**
 * Cria as conexões PDO com o MySQL/MariaDB a partir da configuração.
 *
 * ATTR_EMULATE_PREPARES desligado faz o PDO usar prepared statements
 * nativos do MySQL: a consulta e os valores viajam separados até o
 * servidor, o que impede SQL Injection.
 */
final class Database
{
    private const OPCOES = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    public static function conectar(Config $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config->get('DB_HOST', '127.0.0.1'),
            $config->int('DB_PORT', 3306),
            self::nomeDoBanco($config)
        );
        return new PDO($dsn, $config->exigir('DB_USER'), $config->get('DB_PASS', ''), self::OPCOES);
    }

    /** Conexão sem banco selecionado, usada só para criar o banco nas migrações. */
    public static function conectarServidor(Config $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;charset=utf8mb4',
            $config->get('DB_HOST', '127.0.0.1'),
            $config->int('DB_PORT', 3306)
        );
        return new PDO($dsn, $config->exigir('DB_USER'), $config->get('DB_PASS', ''), self::OPCOES);
    }

    /** Nome do banco validado: entra em CREATE DATABASE, que não aceita parâmetro. */
    public static function nomeDoBanco(Config $config): string
    {
        $nome = $config->exigir('DB_NAME');
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $nome)) {
            throw new InvalidArgumentException('DB_NAME só pode ter letras, números e _.');
        }
        return $nome;
    }
}
