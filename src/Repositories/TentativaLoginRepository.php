<?php

declare(strict_types=1);

namespace Toyota\Repositories;

final class TentativaLoginRepository extends Repositorio
{
    public function registrar(string $ip, ?string $email, bool $sucesso): void
    {
        $this->executar(
            'INSERT INTO tentativas_login (ip, email, sucesso) VALUES (:ip, :email, :sucesso)',
            ['ip' => $ip, 'email' => $email, 'sucesso' => $sucesso]
        );
    }

    /**
     * Falhas do IP dentro da janela de tempo e quantos segundos faltam
     * para a mais antiga sair da janela (usado no cabeçalho Retry-After).
     *
     * @return array{falhas: int, espera: int}
     */
    public function situacao(string $ip, int $janelaSegundos): array
    {
        $linha = $this->linha(
            'SELECT COUNT(*) AS falhas,
                    COALESCE(TIMESTAMPDIFF(SECOND, NOW(), MIN(criado_em) + INTERVAL :janela1 SECOND), 0) AS espera
               FROM tentativas_login
              WHERE ip = :ip AND sucesso = 0 AND criado_em >= NOW() - INTERVAL :janela2 SECOND',
            ['ip' => $ip, 'janela1' => $janelaSegundos, 'janela2' => $janelaSegundos]
        );
        return ['falhas' => (int) ($linha['falhas'] ?? 0), 'espera' => (int) ($linha['espera'] ?? 0)];
    }

    /** Depois de um login certo, as falhas anteriores daquele IP deixam de contar. */
    public function limparFalhas(string $ip): void
    {
        $this->executar('DELETE FROM tentativas_login WHERE ip = :ip AND sucesso = 0', ['ip' => $ip]);
    }
}
