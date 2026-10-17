<?php

declare(strict_types=1);

namespace Toyota\Controllers\Web;

use DateTimeImmutable;
use Toyota\Core\Avisos;
use Toyota\Core\HttpException;
use Toyota\Core\Paginacao;
use Toyota\Core\Request;
use Toyota\Core\Response;
use Toyota\Core\UsuarioAtual;
use Toyota\Core\View;
use Toyota\Services\AgendamentoService;
use Toyota\Services\ModeloService;
use Toyota\Services\RegrasAgendamento;

/** Páginas do cliente: agendar test-drive e acompanhar os próprios agendamentos. */
final class AgendamentoController
{
    public function __construct(
        private View $view,
        private AgendamentoService $agendamentos,
        private ModeloService $modelos,
        private UsuarioAtual $usuarioAtual,
        private Avisos $avisos
    ) {
    }

    public function formulario(Request $requisicao): Response
    {
        $slug = $requisicao->consulta('modelo');
        $antigo = [];
        foreach ($this->modelos->ativos() as $modelo) {
            if ($modelo['slug'] === $slug) {
                $antigo['modelo_id'] = $modelo['id'];
            }
        }
        return $this->tela($antigo, [], null, 200);
    }

    public function criar(Request $requisicao): Response
    {
        try {
            $this->agendamentos->criar($this->usuarioAtual->usuario(), $requisicao->corpo);
        } catch (HttpException $erro) {
            return $this->tela($requisicao->corpo, $erro->campos, $erro->getMessage(), $erro->status);
        }
        $this->avisos->sucesso('Test-drive agendado. A concessionária vai confirmar o horário.');
        return Response::redirecionar($this->view->url('/meus-agendamentos'));
    }

    public function meus(Request $requisicao): Response
    {
        $paginacao = Paginacao::daRequisicao($requisicao);
        [$itens, $meta] = $this->agendamentos->listar($this->usuarioAtual->usuario(), $paginacao, null, true);

        return $this->view->render('meus-agendamentos', [
            'titulo'       => 'Meus agendamentos',
            'agendamentos' => $itens,
            'meta'         => $meta,
            'hoje'         => (new DateTimeImmutable('today'))->format('Y-m-d'),
        ]);
    }

    /** @param array{id: string} $parametros */
    public function cancelar(Request $requisicao, array $parametros): Response
    {
        try {
            $this->agendamentos->alterarStatus($this->usuarioAtual->usuario(), (int) $parametros['id'], 'cancelado');
            $this->avisos->sucesso('Agendamento cancelado.');
        } catch (HttpException $erro) {
            if ($erro->status === 404) {
                throw $erro;
            }
            $this->avisos->erro($erro->getMessage());
        }
        return Response::redirecionar($this->view->url('/meus-agendamentos'));
    }

    /**
     * @param array<string, mixed>  $antigo valores já digitados, para não perder o que a pessoa preencheu
     * @param array<string, string> $erros
     */
    private function tela(array $antigo, array $erros, ?string $mensagem, int $status): Response
    {
        $amanha = new DateTimeImmutable('tomorrow');
        return $this->view->render('agendar', [
            'titulo'      => 'Agendar test-drive',
            'descricao'   => 'Agende um test-drive dos modelos Toyota escolhendo modelo, data e período de preferência.',
            'modelos'     => $this->modelos->ativos(),
            'antigo'      => $antigo,
            'erros'       => $erros,
            'mensagem'    => $mensagem,
            'dataMinima'  => $amanha->format('Y-m-d'),
            'dataMaxima'  => $amanha->modify('+' . (RegrasAgendamento::ANTECEDENCIA_MAXIMA_DIAS - 1) . ' days')->format('Y-m-d'),
        ], $status);
    }
}
