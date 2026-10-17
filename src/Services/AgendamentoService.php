<?php

declare(strict_types=1);

namespace Toyota\Services;

use DateTimeImmutable;
use Toyota\Core\HttpException;
use Toyota\Core\Logger;
use Toyota\Core\Paginacao;
use Toyota\Core\Relogio;
use Toyota\Core\Transacao;
use Toyota\Core\ValidacaoException;
use Toyota\Core\Validador;
use Toyota\Repositories\AgendamentoRepository;
use Toyota\Repositories\ModeloRepository;

/**
 * Casos de uso do agendamento de test-drive (a entidade principal).
 *
 * Quem pode o quê:
 *  - cliente: cria, vê, altera (enquanto pendente) e cancela os PRÓPRIOS;
 *  - admin:   vê todos, confirma, marca como realizado, cancela e exclui.
 *
 * Um cliente que tenta abrir o agendamento de outra pessoa recebe 404, e
 * não 403: a resposta não confirma nem que aquele número existe.
 */
final class AgendamentoService
{
    private const REGRAS = [
        'modelo_id'   => ['obrigatorio', 'inteiro', 'min:1'],
        'data'        => ['obrigatorio', 'texto', 'data'],
        'periodo'     => ['obrigatorio', 'texto', 'em:manha,tarde'],
        'telefone'    => ['opcional', 'texto', 'max:20', 'regex:/^[0-9()+\-\s]{8,20}$/'],
        'observacoes' => ['opcional', 'texto', 'max:400'],
    ];

    public function __construct(
        private AgendamentoRepository $agendamentos,
        private ModeloRepository $modelos,
        private Transacao $transacao,
        private Relogio $relogio,
        private Logger $logger
    ) {
    }

    /**
     * @param array<string, mixed> $usuario   quem está logado
     * @param bool                 $proprios  true = só os do usuário, mesmo sendo admin
     * @return array{0: list<array<string, mixed>>, 1: array<string, int>}
     */
    public function listar(array $usuario, Paginacao $paginacao, ?string $status = null, bool $proprios = false): array
    {
        if ($status !== null) {
            $status = Validador::validar(['status' => $status], [
                'status' => ['obrigatorio', 'texto', 'em:' . implode(',', RegrasAgendamento::STATUS)],
            ])['status'];
        }
        $usuarioId = ($proprios || $usuario['perfil'] !== 'admin') ? $usuario['id'] : null;

        $itens = $this->agendamentos->listar($usuarioId, $status, $paginacao->porPagina, $paginacao->offset());
        return [$itens, $paginacao->meta($this->agendamentos->contar($usuarioId, $status))];
    }

    public function buscar(array $usuario, int $id): array
    {
        $agendamento = $this->agendamentos->buscarPorId($id);
        $visivel = $agendamento !== null
            && ($usuario['perfil'] === 'admin' || $agendamento['usuario_id'] === $usuario['id']);

        if (!$visivel) {
            throw HttpException::naoEncontrado('Agendamento não encontrado.');
        }
        return $agendamento;
    }

    /** @param array<string, mixed> $entrada modelo_id, data, periodo, telefone, observacoes */
    public function criar(array $usuario, array $entrada): array
    {
        $dados = $this->validar($entrada);

        $id = $this->transacao->executar(function () use ($usuario, $dados): int {
            $this->conferirDisponibilidade($usuario['id'], $dados, null);
            return $this->agendamentos->criar($dados + ['usuario_id' => $usuario['id']]);
        });

        $this->logger->info('agendamento.criado', [
            'agendamento_id' => $id,
            'usuario_id'     => $usuario['id'],
            'modelo_id'      => $dados['modelo_id'],
            'data'           => $dados['data'],
            'periodo'        => $dados['periodo'],
        ]);
        return $this->buscar($usuario, $id);
    }

    /**
     * Alteração parcial (PATCH): só os campos enviados mudam.
     * Só agendamentos pendentes podem ser remarcados.
     */
    public function atualizar(array $usuario, int $id, array $entrada): array
    {
        $atual = $this->buscar($usuario, $id);
        if ($atual['status'] !== 'pendente') {
            throw HttpException::conflito('Só é possível alterar agendamentos pendentes.');
        }

        $mesclado = array_intersect_key($entrada, self::REGRAS) + [
            'modelo_id'   => $atual['modelo_id'],
            'data'        => $atual['data'],
            'periodo'     => $atual['periodo'],
            'telefone'    => $atual['telefone'],
            'observacoes' => $atual['observacoes'],
        ];
        $dados = $this->validar($mesclado);

        $this->transacao->executar(function () use ($atual, $dados, $id): void {
            $this->conferirDisponibilidade($atual['usuario_id'], $dados, $id);
            $this->agendamentos->atualizar($id, $dados);
        });

        $this->logger->info('agendamento.atualizado', ['agendamento_id' => $id, 'por' => $usuario['id']]);
        return $this->buscar($usuario, $id);
    }

    public function alterarStatus(array $usuario, int $id, mixed $novoStatus): array
    {
        $novo = Validador::validar(['status' => $novoStatus], [
            'status' => ['obrigatorio', 'texto', 'em:' . implode(',', RegrasAgendamento::STATUS)],
        ])['status'];

        $atual = $this->buscar($usuario, $id);
        $perfil = $usuario['perfil'] === 'admin' ? 'admin' : 'cliente';

        if (!RegrasAgendamento::transicaoPermitida($atual['status'], $novo, $perfil)) {
            throw HttpException::conflito(sprintf(
                'Não é possível mudar o status de "%s" para "%s".',
                $atual['status'],
                $novo
            ));
        }

        $data = new DateTimeImmutable($atual['data']);
        if ($perfil === 'cliente' && !RegrasAgendamento::clientePodeCancelar($data, $this->relogio->hoje())) {
            throw HttpException::conflito('O cancelamento pelo site vai até a véspera. Ligue para a concessionária.');
        }

        $this->agendamentos->alterarStatus($id, $novo);
        $this->logger->info('agendamento.status', [
            'agendamento_id' => $id,
            'de'             => $atual['status'],
            'para'           => $novo,
            'por'            => $usuario['id'],
        ]);
        return $this->buscar($usuario, $id);
    }

    public function excluir(array $usuario, int $id): void
    {
        $this->buscar($usuario, $id);
        $this->agendamentos->excluir($id);
        $this->logger->info('agendamento.excluido', ['agendamento_id' => $id, 'por' => $usuario['id']]);
    }

    /**
     * Formato dos campos + regra da data, com todos os erros juntos: se o
     * período faltar E a data for domingo, a pessoa vê as duas mensagens
     * de uma vez, em vez de corrigir uma e descobrir a outra depois.
     */
    private function validar(array $entrada): array
    {
        $erros = [];
        try {
            $dados = Validador::validar($entrada, self::REGRAS);
        } catch (ValidacaoException $erro) {
            $erros = $erro->campos;
            $dados = null;
        }

        if (!isset($erros['data'])) {
            $data = $dados['data'] ?? Validador::validar($entrada, ['data' => self::REGRAS['data']])['data'];
            $erroData = RegrasAgendamento::erroNaData(new DateTimeImmutable($data), $this->relogio->hoje());
            if ($erroData !== null) {
                $erros['data'] = $erroData;
            }
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros);
        }
        return $dados;
    }

    /** Roda dentro da transação, com a linha do modelo travada. */
    private function conferirDisponibilidade(int $usuarioId, array $dados, ?int $ignorarId): void
    {
        $modelo = $this->modelos->buscarComTrava($dados['modelo_id']);
        if ($modelo === null || !$modelo['ativo']) {
            throw new ValidacaoException(['modelo_id' => 'Modelo não encontrado ou indisponível para test-drive.']);
        }

        if ($this->agendamentos->usuarioTemAtivoNoHorario($usuarioId, $dados['data'], $dados['periodo'], $ignorarId)) {
            throw HttpException::conflito('Você já tem um test-drive marcado nesse dia e período.');
        }

        $ocupados = $this->agendamentos->contarAtivosNoHorario(
            $dados['modelo_id'],
            $dados['data'],
            $dados['periodo'],
            $ignorarId
        );
        if (RegrasAgendamento::horarioLotado($ocupados)) {
            throw HttpException::conflito('Esse período já está lotado para este modelo. Escolha outra data ou período.');
        }
    }
}
