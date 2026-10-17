<?php

declare(strict_types=1);

/*
 * Ponto de entrada único (front controller). Toda requisição passa por
 * aqui: o .htaccess manda para este arquivo tudo que não for um arquivo
 * estático (CSS, imagens).
 */

use Toyota\Core\Config;
use Toyota\Core\Fabrica;
use Toyota\Core\Request;
use Toyota\Core\SessaoNativa;

require dirname(__DIR__) . '/src/bootstrap.php';

$requisicao = Request::capturar();

try {
    $app = Fabrica::criarApp(
        dirname(__DIR__),
        static fn (Config $config) => SessaoNativa::iniciar($config, $requisicao->base)
    );
} catch (Throwable $erro) {
    // Falha antes de a aplicação existir (ex.: config/ corrompido).
    // Os erros comuns, como banco fora do ar, são tratados pelo ErrorHandler.
    error_log('[catalogo-toyota] falha ao iniciar: ' . $erro->getMessage());
    http_response_code(500);
    echo 'Erro interno do servidor.';
    exit;
}

$app->handle($requisicao)->enviar();
