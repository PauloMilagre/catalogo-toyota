<?php

declare(strict_types=1);

namespace Toyota\Core;

use PDO;

/**
 * Aplica as migrações de database/migrations em ordem.
 *
 * Cada arquivo .sql roda uma única vez: o nome dele fica gravado na
 * tabela "migracoes". Para evoluir o banco, cria-se um arquivo novo com
 * o próximo número, em vez de editar os antigos.
 */
final class Migrador
{
    public function __construct(private Config $config, private string $pasta)
    {
    }

    /**
     * @param bool $recriar apaga o banco antes (usado pelos testes)
     * @return list<string> migrações aplicadas nesta execução
     */
    public function migrar(bool $recriar = false): array
    {
        $nomeBanco = Database::nomeDoBanco($this->config);
        $servidor = Database::conectarServidor($this->config);
        if ($recriar) {
            $servidor->exec("DROP DATABASE IF EXISTS `{$nomeBanco}`");
        }
        $servidor->exec(
            "CREATE DATABASE IF NOT EXISTS `{$nomeBanco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        $pdo = Database::conectar($this->config);
        $pdo->exec(self::sqlTabelaMigracoes());

        $aplicadas = $pdo->query('SELECT nome FROM migracoes')->fetchAll(PDO::FETCH_COLUMN);
        $novas = [];

        foreach ($this->arquivos() as $arquivo) {
            $nome = basename($arquivo);
            if (in_array($nome, $aplicadas, true)) {
                continue;
            }
            foreach (self::comandos((string) file_get_contents($arquivo)) as $comando) {
                $pdo->exec($comando);
            }
            $registro = $pdo->prepare('INSERT INTO migracoes (nome) VALUES (?)');
            $registro->execute([$nome]);
            $novas[] = $nome;
        }
        return $novas;
    }

    /** @return list<string> */
    public function arquivos(): array
    {
        $arquivos = glob($this->pasta . '/*.sql') ?: [];
        sort($arquivos, SORT_STRING);
        return $arquivos;
    }

    public static function sqlTabelaMigracoes(): string
    {
        return 'CREATE TABLE IF NOT EXISTS migracoes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(150) NOT NULL,
    aplicada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_migracoes_nome UNIQUE (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    /**
     * Separa um arquivo .sql em comandos (um por ";" no fim da linha)
     * e descarta as linhas de comentário.
     *
     * @return list<string>
     */
    public static function comandos(string $sql): array
    {
        $semComentarios = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $partes = preg_split('/;\s*(\r?\n|$)/', $semComentarios) ?: [];
        return array_values(array_filter(array_map('trim', $partes), static fn (string $c): bool => $c !== ''));
    }
}
