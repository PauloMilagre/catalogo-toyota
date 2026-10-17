<?php

declare(strict_types=1);

namespace Toyota\Controllers\Web;

use Toyota\Core\Avisos;
use Toyota\Core\HttpException;
use Toyota\Core\Logger;
use Toyota\Core\Request;
use Toyota\Core\Response;
use Toyota\Core\View;
use Toyota\Services\AuthService;
use Toyota\Services\SessaoUsuario;

/** Telas de entrar, criar conta e sair. */
final class AuthController
{
    public function __construct(
        private View $view,
        private AuthService $auth,
        private SessaoUsuario $sessaoUsuario,
        private Avisos $avisos,
        private Logger $logger
    ) {
    }

    public function formularioEntrar(Request $requisicao): Response
    {
        return $this->telaEntrar($requisicao, [], null, 200);
    }

    public function entrar(Request $requisicao): Response
    {
        try {
            $usuario = $this->auth->autenticar($requisicao->corpo, $requisicao->ip);
        } catch (HttpException $erro) {
            $resposta = $this->telaEntrar($requisicao, $erro->campos, $erro->getMessage(), $erro->status);
            foreach ($erro->cabecalhos as $nome => $valor) {
                $resposta->comCabecalho($nome, $valor);
            }
            return $resposta;
        }

        $this->sessaoUsuario->entrar($usuario);
        $this->avisos->sucesso('Olá, ' . $usuario['nome'] . '. Você entrou na sua conta.');

        $padrao = $usuario['perfil'] === 'admin' ? '/admin/agendamentos' : '/';
        return Response::redirecionar($this->view->url($this->destinoSeguro($requisicao->entrada('voltar'), $padrao)));
    }

    public function formularioCadastro(Request $requisicao): Response
    {
        return $this->view->render('cadastro', ['titulo' => 'Criar conta', 'erros' => [], 'antigo' => []]);
    }

    public function cadastrar(Request $requisicao): Response
    {
        try {
            $usuario = $this->auth->registrar($requisicao->corpo);
        } catch (HttpException $erro) {
            return $this->view->render('cadastro', [
                'titulo'   => 'Criar conta',
                'erros'    => $erro->campos,
                'mensagem' => $erro->getMessage(),
                'antigo'   => $requisicao->corpo,
            ], $erro->status);
        }

        $this->sessaoUsuario->entrar($usuario);
        $this->avisos->sucesso('Conta criada. Agora é só escolher o modelo e agendar.');
        return Response::redirecionar($this->view->url('/agendar'));
    }

    public function sair(Request $requisicao): Response
    {
        $usuario = $this->sessaoUsuario->usuario();
        $this->sessaoUsuario->sair();
        $this->logger->info('logout', ['usuario_id' => $usuario['id'] ?? null]);
        return Response::redirecionar($this->view->url('/'));
    }

    /** @param array<string, string> $erros */
    private function telaEntrar(Request $requisicao, array $erros, ?string $mensagem, int $status): Response
    {
        return $this->view->render('entrar', [
            'titulo'   => 'Entrar',
            'erros'    => $erros,
            'mensagem' => $mensagem,
            'email'    => is_string($requisicao->entrada('email')) ? $requisicao->entrada('email') : '',
            'voltar'   => $this->destinoSeguro($requisicao->entrada('voltar', $requisicao->consulta('voltar')), ''),
        ], $status);
    }

    /**
     * Só aceita voltar para um caminho DESTE site. Sem isso, um link como
     * /entrar?voltar=https://site-falso.com levaria a pessoa, já logada,
     * para fora (open redirect).
     */
    private function destinoSeguro(mixed $destino, string $padrao): string
    {
        if (!is_string($destino) || !preg_match('#^/(?![/\\\\])[A-Za-z0-9/_\-?=&.%]*$#', $destino)) {
            return $padrao;
        }
        return $destino;
    }
}
