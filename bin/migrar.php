<?php

declare(strict_types=1);

/*
 * Cria o banco (se não existir) e aplica as migrações pendentes.
 *
 *   php bin/migrar.php            aplica o que falta
 *   php bin/migrar.php --recriar  APAGA o banco e cria do zero
 */

use Toyota\Core\Config;
use Toyota\Core\Database;
use Toyota\Core\Migrador;

require dirname(__DIR__) . '/src/bootstrap.php';

$config = Config::carregar(dirname(__DIR__) . '/.env');
$recriar = in_array('--recriar', $argv, true);

try {
    $aplicadas = (new Migrador($config, dirname(__DIR__) . '/database/migrations'))->migrar($recriar);
} catch (PDOException $erro) {
    fwrite(STDERR, "Não foi possível acessar o banco: {$erro->getMessage()}\n");
    fwrite(STDERR, "Confira se o MySQL está ligado no XAMPP e os dados do arquivo .env.\n");
    exit(1);
}

$banco = Database::nomeDoBanco($config);
if ($aplicadas === []) {
    echo "Banco \"{$banco}\" já está atualizado.\n";
} else {
    echo "Banco \"{$banco}\": migrações aplicadas:\n";
    foreach ($aplicadas as $nome) {
        echo "  - {$nome}\n";
    }
}
