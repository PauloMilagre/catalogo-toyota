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

use Toyota\Controllers\Api\AgendamentoApiController;
use Toyota\Controllers\Api\AuthApiController;
use Toyota\Controllers\Api\ModeloApiController;
use Toyota\Controllers\Web\AdminController;
use Toyota\Controllers\Web\AgendamentoController;
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

    // ----- Agendamentos do cliente -----------------------------------------
    $r->get('/agendar', [AgendamentoController::class, 'formulario'], ['auth' => true]);
    $r->post('/agendar', [AgendamentoController::class, 'criar'], ['auth' => true]);
    $r->get('/meus-agendamentos', [AgendamentoController::class, 'meus'], ['auth' => true]);
    $r->post('/agendamentos/{id}/cancelar', [AgendamentoController::class, 'cancelar'], ['auth' => true]);

    // ----- Administração ---------------------------------------------------
    $r->get('/admin/agendamentos', [AdminController::class, 'agendamentos'], ['perfil' => 'admin']);
    $r->post('/admin/agendamentos/{id}/status', [AdminController::class, 'alterarStatus'], ['perfil' => 'admin']);
    $r->post('/admin/agendamentos/{id}/excluir', [AdminController::class, 'excluir'], ['perfil' => 'admin']);

    // ----- API: autenticação -----------------------------------------------
    $r->post('/api/auth/registro', [AuthApiController::class, 'registro'], ['csrf' => false]);
    $r->post('/api/auth/login', [AuthApiController::class, 'login'], ['csrf' => false]);
    $r->post('/api/auth/logout', [AuthApiController::class, 'logout'], ['auth' => true]);
    $r->get('/api/auth/eu', [AuthApiController::class, 'eu'], ['auth' => true]);

    // ----- API: modelos ----------------------------------------------------
    $r->get('/api/modelos', [ModeloApiController::class, 'listar']);
    $r->get('/api/modelos/{id}', [ModeloApiController::class, 'mostrar']);
    $r->post('/api/modelos', [ModeloApiController::class, 'criar'], ['perfil' => 'admin']);
    $r->put('/api/modelos/{id}', [ModeloApiController::class, 'atualizar'], ['perfil' => 'admin']);
    $r->delete('/api/modelos/{id}', [ModeloApiController::class, 'excluir'], ['perfil' => 'admin']);

    // ----- API: agendamentos -----------------------------------------------
    $r->get('/api/agendamentos', [AgendamentoApiController::class, 'listar'], ['auth' => true]);
    $r->get('/api/agendamentos/{id}', [AgendamentoApiController::class, 'mostrar'], ['auth' => true]);
    $r->post('/api/agendamentos', [AgendamentoApiController::class, 'criar'], ['auth' => true]);
    $r->patch('/api/agendamentos/{id}', [AgendamentoApiController::class, 'atualizar'], ['auth' => true]);
    $r->patch('/api/agendamentos/{id}/status', [AgendamentoApiController::class, 'alterarStatus'], ['auth' => true]);
    $r->delete('/api/agendamentos/{id}', [AgendamentoApiController::class, 'excluir'], ['perfil' => 'admin']);
};
