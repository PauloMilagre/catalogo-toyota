<?php

declare(strict_types=1);

namespace Toyota\Controllers\Api;

use Toyota\Core\Paginacao;
use Toyota\Core\Request;
use Toyota\Core\Response;
use Toyota\Core\UsuarioAtual;
use Toyota\Services\AgendamentoService;

final class AgendamentoApiController
{
    public function __construct(private AgendamentoService $agendamentos, private UsuarioAtual $usuarioAtual)
    {
    }

    public function listar(Request $requisicao): Response
    {
        $status = $requisicao->consulta('status');
        [$itens, $meta] = $this->agendamentos->listar(
            $this->usuarioAtual->usuario(),
            Paginacao::daRequisicao($requisicao),
            is_string($status) && $status !== '' ? $status : null
        );
        return Response::json(['dados' => array_map([Formato::class, 'agendamento'], $itens), 'meta' => $meta]);
    }

    /** @param array{id: string} $parametros */
    public function mostrar(Request $requisicao, array $parametros): Response
    {
        $agendamento = $this->agendamentos->buscar($this->usuarioAtual->usuario(), (int) $parametros['id']);
        return Response::json(['dados' => Formato::agendamento($agendamento)]);
    }

    public function criar(Request $requisicao): Response
    {
        $agendamento = $this->agendamentos->criar($this->usuarioAtual->usuario(), $requisicao->corpo);
        return Response::json(['dados' => Formato::agendamento($agendamento)], 201, [
            'Location' => $requisicao->base . '/api/agendamentos/' . $agendamento['id'],
        ]);
    }

    /** @param array{id: string} $parametros */
    public function atualizar(Request $requisicao, array $parametros): Response
    {
        $agendamento = $this->agendamentos->atualizar(
            $this->usuarioAtual->usuario(),
            (int) $parametros['id'],
            $requisicao->corpo
        );
        return Response::json(['dados' => Formato::agendamento($agendamento)]);
    }

    /** @param array{id: string} $parametros */
    public function alterarStatus(Request $requisicao, array $parametros): Response
    {
        $agendamento = $this->agendamentos->alterarStatus(
            $this->usuarioAtual->usuario(),
            (int) $parametros['id'],
            $requisicao->entrada('status')
        );
        return Response::json(['dados' => Formato::agendamento($agendamento)]);
    }

    /** @param array{id: string} $parametros */
    public function excluir(Request $requisicao, array $parametros): Response
    {
        $this->agendamentos->excluir($this->usuarioAtual->usuario(), (int) $parametros['id']);
        return Response::semConteudo();
    }
}
