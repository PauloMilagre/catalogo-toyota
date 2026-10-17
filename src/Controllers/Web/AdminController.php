<?php

declare(strict_types=1);

namespace Toyota\Controllers\Web;

use Toyota\Core\Avisos;
use Toyota\Core\HttpException;
use Toyota\Core\Paginacao;
use Toyota\Core\Request;
use Toyota\Core\Response;
use Toyota\Core\UsuarioAtual;
use Toyota\Core\View;
use Toyota\Services\AgendamentoService;
use Toyota\Services\RegrasAgendamento;

/**
 * Painel do administrador. As rotas deste controller exigem perfil "admin"
 * (config/rotas.php); um cliente que digitar o endereço recebe 403.
 */
final class AdminController
{
    public function __construct(
        private View $view,
        private AgendamentoService $agendamentos,
        private UsuarioAtual $usuarioAtual,
        private Avisos $avisos
    ) {
    }

    public function agendamentos(Request $requisicao): Response
    {
        $status = $requisicao->consulta('status');
        $status = is_string($status) && $status !== '' ? $status : null;
        $paginacao = Paginacao::daRequisicao($requisicao, 20);

        [$itens, $meta] = $this->agendamentos->listar($this->usuarioAtual->usuario(), $paginacao, $status);

        return $this->view->render('admin/agendamentos', [
            'titulo'        => 'Administração de agendamentos',
            'agendamentos'  => $itens,
            'meta'          => $meta,
            'statusFiltro'  => $status,
            'todosStatus'   => RegrasAgendamento::STATUS,
        ]);
    }

    /** @param array{id: string} $parametros */
    public function alterarStatus(Request $requisicao, array $parametros): Response
    {
        try {
            $this->agendamentos->alterarStatus(
                $this->usuarioAtual->usuario(),
                (int) $parametros['id'],
                $requisicao->entrada('status')
            );
            $this->avisos->sucesso('Status do agendamento nº ' . (int) $parametros['id'] . ' atualizado.');
        } catch (HttpException $erro) {
            $this->avisos->erro($erro->getMessage());
        }
        return Response::redirecionar($this->view->url('/admin/agendamentos'));
    }

    /** @param array{id: string} $parametros */
    public function excluir(Request $requisicao, array $parametros): Response
    {
        $this->agendamentos->excluir($this->usuarioAtual->usuario(), (int) $parametros['id']);
        $this->avisos->sucesso('Agendamento nº ' . (int) $parametros['id'] . ' excluído.');
        return Response::redirecionar($this->view->url('/admin/agendamentos'));
    }
}
