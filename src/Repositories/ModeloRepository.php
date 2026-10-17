<?php

declare(strict_types=1);

namespace Toyota\Repositories;

final class ModeloRepository extends Repositorio
{
    /** Colunas que podem ser gravadas (a ordem não importa). */
    public const CAMPOS = [
        'slug', 'nome', 'categoria', 'preco', 'motor', 'potencia_cv', 'cambio', 'consumo_km_l',
        'porta_malas_l', 'carga_kg', 'descricao', 'imagem', 'imagem_alt', 'imagem_largura',
        'imagem_altura', 'legenda', 'ativo',
    ];

    /** @return list<array<string, mixed>> */
    public function listar(bool $apenasAtivos, int $limite, int $deslocamento): array
    {
        $onde = $apenasAtivos ? 'WHERE ativo = 1' : '';
        $linhas = $this->executar(
            "SELECT * FROM modelos {$onde} ORDER BY nome LIMIT :limite OFFSET :deslocamento",
            ['limite' => $limite, 'deslocamento' => $deslocamento]
        )->fetchAll();
        return array_map([$this, 'hidratar'], $linhas);
    }

    public function contar(bool $apenasAtivos): int
    {
        $onde = $apenasAtivos ? 'WHERE ativo = 1' : '';
        return (int) $this->valor("SELECT COUNT(*) FROM modelos {$onde}");
    }

    public function buscarPorId(int $id): ?array
    {
        $linha = $this->linha('SELECT * FROM modelos WHERE id = :id', ['id' => $id]);
        return $linha === null ? null : $this->hidratar($linha);
    }

    public function buscarPorSlug(string $slug): ?array
    {
        $linha = $this->linha('SELECT * FROM modelos WHERE slug = :slug', ['slug' => $slug]);
        return $linha === null ? null : $this->hidratar($linha);
    }

    /**
     * Lê o modelo e trava a linha até o fim da transação. Dois clientes
     * agendando o mesmo modelo ao mesmo tempo ficam em fila, então a
     * contagem de vagas nunca é feita em cima de um número desatualizado.
     */
    public function buscarComTrava(int $id): ?array
    {
        $linha = $this->linha('SELECT * FROM modelos WHERE id = :id FOR UPDATE', ['id' => $id]);
        return $linha === null ? null : $this->hidratar($linha);
    }

    public function slugEmUso(string $slug, ?int $ignorarId = null): bool
    {
        return (bool) $this->valor(
            'SELECT 1 FROM modelos WHERE slug = :slug AND id <> :ignorar',
            ['slug' => $slug, 'ignorar' => $ignorarId ?? 0]
        );
    }

    public function possuiAgendamentos(int $id): bool
    {
        return (bool) $this->valor('SELECT 1 FROM agendamentos WHERE modelo_id = :id LIMIT 1', ['id' => $id]);
    }

    /** @param array<string, mixed> $dados */
    public function criar(array $dados): int
    {
        $dados = $this->filtrar($dados);
        $colunas = implode(', ', array_keys($dados));
        $marcadores = implode(', ', array_map(static fn (string $c): string => ':' . $c, array_keys($dados)));
        $this->executar("INSERT INTO modelos ({$colunas}) VALUES ({$marcadores})", $dados);
        return $this->ultimoId();
    }

    /** @param array<string, mixed> $dados */
    public function atualizar(int $id, array $dados): void
    {
        $dados = $this->filtrar($dados);
        $atribuicoes = implode(', ', array_map(static fn (string $c): string => "{$c} = :{$c}", array_keys($dados)));
        $this->executar("UPDATE modelos SET {$atribuicoes} WHERE id = :id", $dados + ['id' => $id]);
    }

    public function excluir(int $id): void
    {
        $this->executar('DELETE FROM modelos WHERE id = :id', ['id' => $id]);
    }

    /**
     * Mantém só colunas conhecidas. Os NOMES de coluna entram no SQL, então
     * nunca podem vir de fora: só os desta lista fixa passam.
     *
     * @param array<string, mixed> $dados
     * @return array<string, mixed>
     */
    private function filtrar(array $dados): array
    {
        return array_intersect_key($dados, array_flip(self::CAMPOS));
    }

    /** @param array<string, mixed> $linha */
    private function hidratar(array $linha): array
    {
        foreach (['id', 'potencia_cv'] as $campo) {
            $linha[$campo] = (int) $linha[$campo];
        }
        foreach (['porta_malas_l', 'carga_kg', 'imagem_largura', 'imagem_altura'] as $campo) {
            $linha[$campo] = $linha[$campo] === null ? null : (int) $linha[$campo];
        }
        $linha['preco'] = (float) $linha['preco'];
        $linha['consumo_km_l'] = (float) $linha['consumo_km_l'];
        $linha['ativo'] = (bool) $linha['ativo'];
        return $linha;
    }
}
