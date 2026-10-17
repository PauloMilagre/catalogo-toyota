<?php

declare(strict_types=1);

namespace Toyota\Core;

use Throwable;

/**
 * Recebe uma requisição e devolve uma resposta. Toda requisição passa
 * pelas mesmas verificações, nesta ordem, antes de chegar ao controller:
 *
 *   1. a rota existe?                         (senão 404/405)
 *   2. a rota exige login e ninguém entrou?   (401)
 *   3. a rota exige um perfil que o usuário não tem?  (403)
 *   4. a API recebeu JSON válido?             (415/400)
 *   5. o token CSRF confere?                  (403)
 *
 * Como as regras ficam na definição da rota (config/rotas.php), nenhum
 * controller precisa lembrar de verificar permissão por conta própria.
 */
final class App
{
    private const METODOS_COM_CORPO = ['POST', 'PUT', 'PATCH'];

    public function __construct(private Container $container, private Router $router)
    {
    }

    public function handle(Request $requisicao): Response
    {
        try {
            $this->container->get(View::class)->contexto($requisicao, null);
            $resposta = $this->despachar($requisicao);
        } catch (Throwable $erro) {
            $resposta = $this->container->get(ErrorHandler::class)->resposta($erro, $requisicao);
        }
        return $this->cabecalhosDeSeguranca($resposta);
    }

    private function despachar(Request $requisicao): Response
    {
        $usuario = $this->container->get(UsuarioAtual::class)->usuario();
        $this->container->get(View::class)->contexto($requisicao, $usuario);

        [$rota, $parametros] = $this->router->resolver($requisicao->metodo, $requisicao->caminho);

        if ($rota->exigeLogin() && $usuario === null) {
            throw new HttpException(401, 'Faça login para continuar.');
        }

        if ($rota->perfil !== null && $usuario['perfil'] !== $rota->perfil) {
            $this->container->get(Logger::class)->aviso('acesso.negado', [
                'usuario_id' => $usuario['id'],
                'perfil'     => $usuario['perfil'],
                'caminho'    => $requisicao->caminho,
            ]);
            throw HttpException::proibido();
        }

        if ($requisicao->ehApi() && in_array($requisicao->metodo, self::METODOS_COM_CORPO, true)) {
            if (!$requisicao->corpoEmJson()) {
                throw new HttpException(415, 'Envie o corpo em JSON (Content-Type: application/json).');
            }
            if ($requisicao->corpoInvalido) {
                throw new HttpException(400, 'O corpo da requisição não é um JSON válido.');
            }
        }

        if ($rota->csrf) {
            $token = $requisicao->cabecalho('x-csrf-token') ?? $requisicao->entrada('_csrf');
            try {
                $this->container->get(Csrf::class)->validar($token);
            } catch (HttpException $erro) {
                $this->container->get(Logger::class)->aviso('csrf.invalido', [
                    'caminho' => $requisicao->caminho,
                    'ip'      => $requisicao->ip,
                ]);
                throw $erro;
            }
        }

        [$classe, $metodo] = $rota->acao;
        return $this->container->get($classe)->$metodo($requisicao, $parametros);
    }

    /** Cabeçalhos enviados em todas as respostas. */
    private function cabecalhosDeSeguranca(Response $resposta): Response
    {
        return $resposta
            ->comCabecalho('X-Content-Type-Options', 'nosniff')
            ->comCabecalho('X-Frame-Options', 'DENY')
            ->comCabecalho('Referrer-Policy', 'same-origin')
            ->comCabecalho(
                'Content-Security-Policy',
                "default-src 'self'; img-src 'self'; style-src 'self'; script-src 'none'; "
                . "form-action 'self'; frame-ancestors 'none'; base-uri 'none'"
            );
    }
}
