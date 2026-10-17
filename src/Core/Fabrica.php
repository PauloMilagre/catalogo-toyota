<?php

declare(strict_types=1);

namespace Toyota\Core;

/**
 * Monta a aplicação: lê a configuração, registra os componentes
 * (config/servicos.php) e as rotas (config/rotas.php).
 * Usada pelo public/index.php e pelos testes de integração.
 */
final class Fabrica
{
    /**
     * @param callable(Config): Sessao $criarSessao
     */
    public static function criarApp(string $raiz, callable $criarSessao, ?Config $config = null): App
    {
        $config ??= Config::carregar($raiz . '/.env');
        date_default_timezone_set($config->get('APP_TIMEZONE', 'America/Sao_Paulo'));

        $container = new Container();
        $container->instancia(Config::class, $config);
        $container->instancia(Sessao::class, $criarSessao($config));

        $registrarServicos = require $raiz . '/config/servicos.php';
        $registrarServicos($container, $config, $raiz);

        $router = new Router();
        $registrarRotas = require $raiz . '/config/rotas.php';
        $registrarRotas($router);

        return new App($container, $router);
    }
}
