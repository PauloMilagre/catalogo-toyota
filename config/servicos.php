<?php

declare(strict_types=1);

/*
 * Receitas de montagem de cada componente (quem depende de quem).
 * Cada um é criado só quando alguém precisa dele, e uma vez por requisição.
 */

use Toyota\Controllers\Api\AgendamentoApiController;
use Toyota\Controllers\Api\AuthApiController;
use Toyota\Controllers\Api\ModeloApiController;
use Toyota\Controllers\Web\AdminController;
use Toyota\Controllers\Web\AgendamentoController;
use Toyota\Controllers\Web\AuthController;
use Toyota\Controllers\Web\PaginaController;
use Toyota\Core\Avisos;
use Toyota\Core\Config;
use Toyota\Core\Container;
use Toyota\Core\Csrf;
use Toyota\Core\Database;
use Toyota\Core\ErrorHandler;
use Toyota\Core\Logger;
use Toyota\Core\Relogio;
use Toyota\Core\Sessao;
use Toyota\Core\Transacao;
use Toyota\Core\UsuarioAtual;
use Toyota\Core\View;
use Toyota\Repositories\AgendamentoRepository;
use Toyota\Repositories\ModeloRepository;
use Toyota\Repositories\TentativaLoginRepository;
use Toyota\Repositories\UsuarioRepository;
use Toyota\Services\AgendamentoService;
use Toyota\Services\AuthService;
use Toyota\Services\ModeloService;
use Toyota\Services\SessaoUsuario;

return static function (Container $c, Config $config, string $raiz): void {
    // Infraestrutura
    $c->definir(PDO::class, static fn () => Database::conectar($config));
    $c->definir(Transacao::class, static fn (Container $c) => new Transacao($c->get(PDO::class)));
    $c->definir(Relogio::class, static fn () => new Relogio());
    $c->definir(Logger::class, static function () use ($config, $raiz): Logger {
        $arquivo = $config->get('LOG_ARQUIVO', 'logs/app.log');
        $absoluto = preg_match('#^([A-Za-z]:)?[/\\\\]#', $arquivo) ? $arquivo : $raiz . '/' . $arquivo;
        return new Logger($absoluto);
    });
    $c->definir(Csrf::class, static fn (Container $c) => new Csrf($c->get(Sessao::class)));
    $c->definir(Avisos::class, static fn (Container $c) => new Avisos($c->get(Sessao::class)));
    $c->definir(View::class, static fn (Container $c) => new View(
        $raiz . '/src/Views',
        '',
        $c->get(Csrf::class),
        $c->get(Avisos::class)
    ));
    $c->definir(ErrorHandler::class, static fn (Container $c) => new ErrorHandler(
        $c->get(Logger::class),
        $config->modoDesenvolvimento(),
        $c->get(View::class)
    ));

    // Acesso a dados
    $c->definir(UsuarioRepository::class, static fn (Container $c) => new UsuarioRepository($c->get(PDO::class)));
    $c->definir(ModeloRepository::class, static fn (Container $c) => new ModeloRepository($c->get(PDO::class)));
    $c->definir(AgendamentoRepository::class, static fn (Container $c) => new AgendamentoRepository($c->get(PDO::class)));
    $c->definir(TentativaLoginRepository::class, static fn (Container $c) => new TentativaLoginRepository($c->get(PDO::class)));

    // Regras de negócio
    $c->definir(SessaoUsuario::class, static fn (Container $c) => new SessaoUsuario(
        $c->get(Sessao::class),
        $c->get(UsuarioRepository::class),
        $c->get(Csrf::class)
    ));
    $c->definir(UsuarioAtual::class, static fn (Container $c) => $c->get(SessaoUsuario::class));
    $c->definir(AuthService::class, static fn (Container $c) => new AuthService(
        $c->get(UsuarioRepository::class),
        $c->get(TentativaLoginRepository::class),
        $c->get(Logger::class)
    ));
    $c->definir(ModeloService::class, static fn (Container $c) => new ModeloService(
        $c->get(ModeloRepository::class),
        $c->get(Logger::class)
    ));
    $c->definir(AgendamentoService::class, static fn (Container $c) => new AgendamentoService(
        $c->get(AgendamentoRepository::class),
        $c->get(ModeloRepository::class),
        $c->get(Transacao::class),
        $c->get(Relogio::class),
        $c->get(Logger::class)
    ));

    // Controllers das páginas
    $c->definir(PaginaController::class, static fn (Container $c) => new PaginaController(
        $c->get(View::class),
        $c->get(ModeloService::class)
    ));
    $c->definir(AuthController::class, static fn (Container $c) => new AuthController(
        $c->get(View::class),
        $c->get(AuthService::class),
        $c->get(SessaoUsuario::class),
        $c->get(Avisos::class),
        $c->get(Logger::class)
    ));
    $c->definir(AgendamentoController::class, static fn (Container $c) => new AgendamentoController(
        $c->get(View::class),
        $c->get(AgendamentoService::class),
        $c->get(ModeloService::class),
        $c->get(UsuarioAtual::class),
        $c->get(Avisos::class)
    ));
    $c->definir(AdminController::class, static fn (Container $c) => new AdminController(
        $c->get(View::class),
        $c->get(AgendamentoService::class),
        $c->get(UsuarioAtual::class),
        $c->get(Avisos::class)
    ));

    // Controllers da API
    $c->definir(AuthApiController::class, static fn (Container $c) => new AuthApiController(
        $c->get(AuthService::class),
        $c->get(SessaoUsuario::class),
        $c->get(Csrf::class),
        $c->get(Logger::class)
    ));
    $c->definir(ModeloApiController::class, static fn (Container $c) => new ModeloApiController(
        $c->get(ModeloService::class),
        $c->get(UsuarioAtual::class)
    ));
    $c->definir(AgendamentoApiController::class, static fn (Container $c) => new AgendamentoApiController(
        $c->get(AgendamentoService::class),
        $c->get(UsuarioAtual::class)
    ));
};
