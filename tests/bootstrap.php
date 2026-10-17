<?php

declare(strict_types=1);

/*
 * Preparação dos testes: carrega as classes da aplicação e as auxiliares
 * de teste (namespace Toyota\Testes\, pasta tests/), e manda os logs
 * para logs/teste.log, separado do log da aplicação.
 */

require dirname(__DIR__) . '/src/bootstrap.php';

spl_autoload_register(static function (string $classe): void {
    $prefixo = 'Toyota\\Testes\\';
    if (str_starts_with($classe, $prefixo)) {
        $arquivo = __DIR__ . '/' . str_replace('\\', '/', substr($classe, strlen($prefixo))) . '.php';
        if (is_file($arquivo)) {
            require $arquivo;
        }
    }
});

putenv('APP_ENV=teste');
putenv('APP_DEBUG=false');
putenv('LOG_ARQUIVO=logs/teste.log');
date_default_timezone_set('America/Sao_Paulo');
