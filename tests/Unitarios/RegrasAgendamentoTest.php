<?php

declare(strict_types=1);

namespace Toyota\Testes\Unitarios;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Toyota\Services\RegrasAgendamento;

/** Regras de negócio do agendamento, com "hoje" fixo numa sexta-feira. */
final class RegrasAgendamentoTest extends TestCase
{
    private DateTimeImmutable $hoje;

    protected function setUp(): void
    {
        $this->hoje = new DateTimeImmutable('2026-10-16'); // sexta-feira
    }

    private function erro(string $data): ?string
    {
        return RegrasAgendamento::erroNaData(new DateTimeImmutable($data), $this->hoje);
    }

    public function testAmanhaEhAceito(): void
    {
        $this->assertNull($this->erro('2026-10-17')); // sábado
    }

    public function testHojeEDatasPassadasSaoRecusadas(): void
    {
        $this->assertSame('Escolha uma data a partir de amanhã.', $this->erro('2026-10-16'));
        $this->assertSame('Escolha uma data a partir de amanhã.', $this->erro('2025-01-10'));
    }

    public function testDomingoEhRecusado(): void
    {
        $this->assertSame('A concessionária não abre aos domingos.', $this->erro('2026-10-18'));
    }

    public function testLimiteDeSessentaDias(): void
    {
        $this->assertNull($this->erro('2026-12-15'));   // hoje + 60 (terça)
        $this->assertSame(
            'É possível agendar com até 60 dias de antecedência.',
            $this->erro('2026-12-16')                    // hoje + 61
        );
    }

    public function testClienteSoPodeCancelar(): void
    {
        $this->assertTrue(RegrasAgendamento::transicaoPermitida('pendente', 'cancelado', 'cliente'));
        $this->assertTrue(RegrasAgendamento::transicaoPermitida('confirmado', 'cancelado', 'cliente'));
        $this->assertFalse(RegrasAgendamento::transicaoPermitida('pendente', 'confirmado', 'cliente'));
        $this->assertFalse(RegrasAgendamento::transicaoPermitida('confirmado', 'realizado', 'cliente'));
    }

    public function testAdministradorConduzOFluxo(): void
    {
        $this->assertTrue(RegrasAgendamento::transicaoPermitida('pendente', 'confirmado', 'admin'));
        $this->assertTrue(RegrasAgendamento::transicaoPermitida('confirmado', 'realizado', 'admin'));
        $this->assertFalse(RegrasAgendamento::transicaoPermitida('pendente', 'realizado', 'admin'));
    }

    public function testStatusFinaisNaoMudamMais(): void
    {
        foreach (['cancelado', 'realizado'] as $final) {
            foreach (RegrasAgendamento::STATUS as $novo) {
                $this->assertFalse(RegrasAgendamento::transicaoPermitida($final, $novo, 'admin'));
            }
        }
    }

    public function testPerfilDesconhecidoNaoMudaNada(): void
    {
        $this->assertFalse(RegrasAgendamento::transicaoPermitida('pendente', 'cancelado', 'visitante'));
    }

    public function testCancelamentoPeloClienteVaiAteAVespera(): void
    {
        $this->assertTrue(RegrasAgendamento::clientePodeCancelar(new DateTimeImmutable('2026-10-17'), $this->hoje));
        $this->assertFalse(RegrasAgendamento::clientePodeCancelar(new DateTimeImmutable('2026-10-16'), $this->hoje));
    }

    public function testTerceiroAgendamentoLotaOPeriodo(): void
    {
        $this->assertFalse(RegrasAgendamento::horarioLotado(2));
        $this->assertTrue(RegrasAgendamento::horarioLotado(3));
    }
}
