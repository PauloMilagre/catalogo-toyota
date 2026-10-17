<?php

declare(strict_types=1);

namespace Toyota\Controllers\Api;

use Toyota\Core\Paginacao;
use Toyota\Core\Request;
use Toyota\Core\Response;
use Toyota\Core\UsuarioAtual;
use Toyota\Services\ModeloService;

final class ModeloApiController
{
    public function __construct(private ModeloService $modelos, private UsuarioAtual $usuarioAtual)
    {
    }

    public function listar(Request $requisicao): Response
    {
        $usuario = $this->usuarioAtual->usuario();
        $incluirInativos = ($usuario['perfil'] ?? null) === 'admin' && $requisicao->consulta('todos') === '1';

        [$itens, $meta] = $this->modelos->listar(Paginacao::daRequisicao($requisicao), $incluirInativos);
        return Response::json(['dados' => array_map([Formato::class, 'modelo'], $itens), 'meta' => $meta]);
    }

    /** @param array{id: string} $parametros */
    public function mostrar(Request $requisicao, array $parametros): Response
    {
        $ehAdmin = ($this->usuarioAtual->usuario()['perfil'] ?? null) === 'admin';
        $modelo = $this->modelos->porId((int) $parametros['id'], $ehAdmin);
        return Response::json(['dados' => Formato::modelo($modelo)]);
    }

    public function criar(Request $requisicao): Response
    {
        $modelo = $this->modelos->criar($requisicao->corpo, $this->usuarioAtual->usuario()['id']);
        return Response::json(['dados' => Formato::modelo($modelo)], 201, [
            'Location' => $requisicao->base . '/api/modelos/' . $modelo['id'],
        ]);
    }

    /** @param array{id: string} $parametros */
    public function atualizar(Request $requisicao, array $parametros): Response
    {
        $modelo = $this->modelos->atualizar((int) $parametros['id'], $requisicao->corpo, $this->usuarioAtual->usuario()['id']);
        return Response::json(['dados' => Formato::modelo($modelo)]);
    }

    /** @param array{id: string} $parametros */
    public function excluir(Request $requisicao, array $parametros): Response
    {
        $this->modelos->excluir((int) $parametros['id'], $this->usuarioAtual->usuario()['id']);
        return Response::semConteudo();
    }
}
