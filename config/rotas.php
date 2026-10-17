<?php

declare(strict_types=1);

/*
 * Todas as URLs do sistema e quem pode acessar cada uma.
 *
 *   (sem opção)          público
 *   ['auth' => true]     precisa estar logado (qualquer perfil)
 *   ['perfil' => 'admin'] precisa estar logado como administrador
 *   ['csrf' => false]    dispensa o token CSRF (só login e cadastro da API,
 *                        que ainda não têm sessão; o padrão é exigir em
 *                        todo POST, PUT, PATCH e DELETE)
 *
 * A matriz de permissões completa está em docs/arquitetura.md.
 */

use Toyota\Controllers\Api\AuthApiController;
use Toyota\Controllers\Web\AuthController;
use Toyota\Controllers\Web\PaginaController;
use Toyota\Core\Router;

return static function (Router $r): void {
    // ----- Páginas públicas ------------------------------------------------
    $r->get('/', [PaginaController::class, 'catalogo']);
    $r->get('/modelos/{slug}', [PaginaController::class, 'ficha']);

    // ----- Conta -----------------------------------------------------------
    $r->get('/entrar', [AuthController::class, 'formularioEntrar']);
    $r->post('/entrar', [AuthController::class, 'entrar']);
    $r->get('/cadastro', [AuthController::class, 'formularioCadastro']);
    $r->post('/cadastro', [AuthController::class, 'cadastrar']);
    $r->post('/sair', [AuthController::class, 'sair'], ['auth' => true]);

    // ----- API: autenticação -----------------------------------------------
    $r->post('/api/auth/registro', [AuthApiController::class, 'registro'], ['csrf' => false]);
    $r->post('/api/auth/login', [AuthApiController::class, 'login'], ['csrf' => false]);
    $r->post('/api/auth/logout', [AuthApiController::class, 'logout'], ['auth' => true]);
    $r->get('/api/auth/eu', [AuthApiController::class, 'eu'], ['auth' => true]);
};
