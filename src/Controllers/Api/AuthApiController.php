<?php

declare(strict_types=1);

namespace Toyota\Controllers\Api;

use Toyota\Core\Csrf;
use Toyota\Core\Logger;
use Toyota\Core\Request;
use Toyota\Core\Response;
use Toyota\Services\AuthService;
use Toyota\Services\SessaoUsuario;

/**
 * Autenticação da API por sessão (o mesmo cookie do site).
 * O login devolve o csrf_token, que o cliente da API manda no cabeçalho
 * X-CSRF-Token em todo POST, PUT, PATCH e DELETE.
 */
final class AuthApiController
{
    public function __construct(
        private AuthService $auth,
        private SessaoUsuario $sessaoUsuario,
        private Csrf $csrf,
        private Logger $logger
    ) {
    }

    public function registro(Request $requisicao): Response
    {
        $usuario = $this->auth->registrar($requisicao->corpo);
        return Response::json(['dados' => Formato::usuario($usuario)], 201);
    }

    public function login(Request $requisicao): Response
    {
        $usuario = $this->auth->autenticar($requisicao->corpo, $requisicao->ip);
        $token = $this->sessaoUsuario->entrar($usuario);
        return Response::json(['dados' => ['usuario' => Formato::usuario($usuario), 'csrf_token' => $token]]);
    }

    public function logout(Request $requisicao): Response
    {
        $usuario = $this->sessaoUsuario->usuario();
        $this->sessaoUsuario->sair();
        $this->logger->info('logout', ['usuario_id' => $usuario['id'] ?? null]);
        return Response::semConteudo();
    }

    public function eu(Request $requisicao): Response
    {
        return Response::json(['dados' => [
            'usuario'    => Formato::usuario($this->sessaoUsuario->usuario()),
            'csrf_token' => $this->csrf->token(),
        ]]);
    }
}
