<?php

declare(strict_types=1);

namespace Toyota\Services;

use DateTimeImmutable;

/**
 * Regras de negócio do agendamento de test-drive, sem banco de dados.
 * Por não depender de nada externo, são testadas isoladamente
 * em tests/Unitarios/RegrasAgendamentoTest.php.
 */
final class RegrasAgendamento
{
    public const PERIODOS = ['manha', 'tarde'];
    public const STATUS = ['pendente', 'confirmado', 'cancelado', 'realizado'];
    public const STATUS_ATIVOS = ['pendente', 'confirmado'];

    /** Até quantos dias à frente se pode agendar. */
    public const ANTECEDENCIA_MAXIMA_DIAS = 60;

    /** Carros de cada modelo disponíveis para test-drive no mesmo período. */
    public const VAGAS_POR_PERIODO = 3;

    /**
     * Transições de status permitidas para cada perfil.
     * O cliente só pode cancelar; o administrador conduz o fluxo.
     */
    private const TRANSICOES = [
        'admin' => [
            'pendente'   => ['confirmado', 'cancelado'],
            'confirmado' => ['realizado', 'cancelado'],
        ],
        'cliente' => [
            'pendente'   => ['cancelado'],
            'confirmado' => ['cancelado'],
        ],
    ];

    /** @return ?string mensagem de erro, ou null se a data for aceita */
    public static function erroNaData(DateTimeImmutable $data, DateTimeImmutable $hoje): ?string
    {
        $data = $data->setTime(0, 0);
        $hoje = $hoje->setTime(0, 0);

        if ($data <= $hoje) {
            return 'Escolha uma data a partir de amanhã.';
        }
        if ($data > $hoje->modify('+' . self::ANTECEDENCIA_MAXIMA_DIAS . ' days')) {
            return 'É possível agendar com até ' . self::ANTECEDENCIA_MAXIMA_DIAS . ' dias de antecedência.';
        }
        if ($data->format('N') === '7') {
            return 'A concessionária não abre aos domingos.';
        }
        return null;
    }

    public static function transicaoPermitida(string $atual, string $novo, string $perfil): bool
    {
        return in_array($novo, self::TRANSICOES[$perfil][$atual] ?? [], true);
    }

    /** @return list<string> status para os quais o perfil pode mudar a partir do atual */
    public static function proximosStatus(string $atual, string $perfil): array
    {
        return self::TRANSICOES[$perfil][$atual] ?? [];
    }

    /** O cliente pode cancelar até a véspera; no próprio dia, só a concessionária. */
    public static function clientePodeCancelar(DateTimeImmutable $data, DateTimeImmutable $hoje): bool
    {
        return $data->setTime(0, 0) > $hoje->setTime(0, 0);
    }

    public static function horarioLotado(int $ocupados): bool
    {
        return $ocupados >= self::VAGAS_POR_PERIODO;
    }
}
