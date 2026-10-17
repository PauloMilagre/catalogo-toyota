<?php

declare(strict_types=1);

namespace Toyota\Repositories;

final class AgendamentoRepository extends Repositorio
{
    private const SELECAO = '
        SELECT a.id, a.usuario_id, a.modelo_id, a.data, a.periodo, a.telefone, a.observacoes,
               a.status, a.criado_em, a.atualizado_em,
               m.slug AS modelo_slug, m.nome AS modelo_nome,
               u.nome AS usuario_nome, u.email AS usuario_email
          FROM agendamentos a
          JOIN modelos m ON m.id = a.modelo_id
          JOIN usuarios u ON u.id = a.usuario_id';

    /** @param array<string, mixed> $dados */
    public function criar(array $dados): int
    {
        $this->executar(
            'INSERT INTO agendamentos (usuario_id, modelo_id, data, periodo, telefone, observacoes)
             VALUES (:usuario_id, :modelo_id, :data, :periodo, :telefone, :observacoes)',
            [
                'usuario_id'  => $dados['usuario_id'],
                'modelo_id'   => $dados['modelo_id'],
                'data'        => $dados['data'],
                'periodo'     => $dados['periodo'],
                'telefone'    => $dados['telefone'],
                'observacoes' => $dados['observacoes'],
            ]
        );
        return $this->ultimoId();
    }

    public function buscarPorId(int $id): ?array
    {
        $linha = $this->linha(self::SELECAO . ' WHERE a.id = :id', ['id' => $id]);
        return $linha === null ? null : $this->hidratar($linha);
    }

    /**
     * @param ?int    $usuarioId null = todos os usuários (administrador)
     * @param ?string $status    null = todos os status
     * @return list<array<string, mixed>>
     */
    public function listar(?int $usuarioId, ?string $status, int $limite, int $deslocamento): array
    {
        [$onde, $parametros] = $this->filtros($usuarioId, $status);
        $linhas = $this->executar(
            self::SELECAO . " {$onde} ORDER BY a.data DESC, a.periodo ASC, a.id DESC
             LIMIT :limite OFFSET :deslocamento",
            $parametros + ['limite' => $limite, 'deslocamento' => $deslocamento]
        )->fetchAll();
        return array_map([$this, 'hidratar'], $linhas);
    }

    public function contar(?int $usuarioId, ?string $status): int
    {
        [$onde, $parametros] = $this->filtros($usuarioId, $status);
        return (int) $this->valor("SELECT COUNT(*) FROM agendamentos a {$onde}", $parametros);
    }

    /** Agendamentos ativos (pendentes ou confirmados) do modelo naquele dia e período. */
    public function contarAtivosNoHorario(int $modeloId, string $data, string $periodo, ?int $ignorarId = null): int
    {
        return (int) $this->valor(
            "SELECT COUNT(*) FROM agendamentos
              WHERE modelo_id = :modelo AND data = :data AND periodo = :periodo
                AND status IN ('pendente', 'confirmado') AND id <> :ignorar",
            ['modelo' => $modeloId, 'data' => $data, 'periodo' => $periodo, 'ignorar' => $ignorarId ?? 0]
        );
    }

    public function usuarioTemAtivoNoHorario(int $usuarioId, string $data, string $periodo, ?int $ignorarId = null): bool
    {
        return (bool) $this->valor(
            "SELECT 1 FROM agendamentos
              WHERE usuario_id = :usuario AND data = :data AND periodo = :periodo
                AND status IN ('pendente', 'confirmado') AND id <> :ignorar LIMIT 1",
            ['usuario' => $usuarioId, 'data' => $data, 'periodo' => $periodo, 'ignorar' => $ignorarId ?? 0]
        );
    }

    /** @param array<string, mixed> $dados modelo_id, data, periodo, telefone, observacoes */
    public function atualizar(int $id, array $dados): void
    {
        $this->executar(
            'UPDATE agendamentos
                SET modelo_id = :modelo_id, data = :data, periodo = :periodo,
                    telefone = :telefone, observacoes = :observacoes
              WHERE id = :id',
            [
                'modelo_id'   => $dados['modelo_id'],
                'data'        => $dados['data'],
                'periodo'     => $dados['periodo'],
                'telefone'    => $dados['telefone'],
                'observacoes' => $dados['observacoes'],
                'id'          => $id,
            ]
        );
    }

    public function alterarStatus(int $id, string $status): void
    {
        $this->executar('UPDATE agendamentos SET status = :status WHERE id = :id', ['status' => $status, 'id' => $id]);
    }

    public function excluir(int $id): void
    {
        $this->executar('DELETE FROM agendamentos WHERE id = :id', ['id' => $id]);
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function filtros(?int $usuarioId, ?string $status): array
    {
        $condicoes = [];
        $parametros = [];
        if ($usuarioId !== null) {
            $condicoes[] = 'a.usuario_id = :usuario_id';
            $parametros['usuario_id'] = $usuarioId;
        }
        if ($status !== null) {
            $condicoes[] = 'a.status = :status';
            $parametros['status'] = $status;
        }
        $onde = $condicoes === [] ? '' : 'WHERE ' . implode(' AND ', $condicoes);
        return [$onde, $parametros];
    }

    /** @param array<string, mixed> $linha */
    private function hidratar(array $linha): array
    {
        foreach (['id', 'usuario_id', 'modelo_id'] as $campo) {
            $linha[$campo] = (int) $linha[$campo];
        }
        return $linha;
    }
}
