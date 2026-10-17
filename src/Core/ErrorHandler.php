<?php

declare(strict_types=1);

namespace Toyota\Core;

use Throwable;

/**
 * Tratamento centralizado de erros: toda exceção que escapa de um
 * controller termina aqui e vira uma resposta padronizada.
 *
 * API (JSON):
 *   {"erro": {"status": 422, "mensagem": "...", "campos": {"data": "..."}}}
 * Páginas: a página de erro com o mesmo código HTTP.
 *
 * Erros inesperados (500) são registrados no log com os detalhes técnicos.
 * Esses detalhes só aparecem na resposta em modo de desenvolvimento
 * (APP_ENV=desenvolvimento e APP_DEBUG=true).
 */
final class ErrorHandler
{
    public function __construct(
        private Logger $logger,
        private bool $modoDesenvolvimento,
        private View $view
    ) {
    }

    public function resposta(Throwable $erro, Request $requisicao): Response
    {
        if ($erro instanceof HttpException) {
            $status = $erro->status;
            $mensagem = $erro->getMessage();
            $campos = $erro->campos;
            $cabecalhos = $erro->cabecalhos;
        } else {
            $status = 500;
            $mensagem = 'Erro interno do servidor. Tente novamente mais tarde.';
            $campos = [];
            $cabecalhos = [];
            $this->logger->erro('erro.interno', [
                'tipo'     => $erro::class,
                'mensagem' => $erro->getMessage(),
                'arquivo'  => $erro->getFile() . ':' . $erro->getLine(),
                'metodo'   => $requisicao->metodo,
                'caminho'  => $requisicao->caminho,
            ]);
        }

        $detalhes = ($status === 500 && $this->modoDesenvolvimento) ? $this->detalhes($erro) : null;

        if ($requisicao->ehApi()) {
            $corpo = ['status' => $status, 'mensagem' => $mensagem];
            if ($campos !== []) {
                $corpo['campos'] = $campos;
            }
            if ($detalhes !== null) {
                $corpo['detalhes'] = $detalhes;
            }
            return Response::json(['erro' => $corpo], $status, $cabecalhos);
        }

        // Página que exige login: manda para o login e volta depois.
        if ($status === 401) {
            return Response::redirecionar($this->view->url('/entrar', ['voltar' => $requisicao->caminhoCompleto()]));
        }

        try {
            $resposta = $this->view->render('erro', [
                'titulo'   => 'Erro ' . $status,
                'status'   => $status,
                'mensagem' => $mensagem,
                'detalhes' => $detalhes,
            ], $status);
        } catch (Throwable) {
            $resposta = Response::html('<h1>Erro ' . $status . '</h1>', $status);
        }
        foreach ($cabecalhos as $nome => $valor) {
            $resposta->comCabecalho($nome, $valor);
        }
        return $resposta;
    }

    /** @return array{tipo: string, mensagem: string, arquivo: string, pilha: list<string>} */
    private function detalhes(Throwable $erro): array
    {
        return [
            'tipo'     => $erro::class,
            'mensagem' => $erro->getMessage(),
            'arquivo'  => $erro->getFile() . ':' . $erro->getLine(),
            'pilha'    => array_slice(explode("\n", $erro->getTraceAsString()), 0, 10),
        ];
    }
}
