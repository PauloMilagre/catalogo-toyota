<?php

declare(strict_types=1);

namespace Toyota\Testes\Integracao;

/** CRUD do agendamento (entidade principal) e suas regras de negócio. */
final class AgendamentoTest extends CasoDeTesteIntegracao
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->criarConta('cliente@teste.com');
        $this->entrarComo('cliente@teste.com');
    }

    public function testClienteCriaAgendamento(): void
    {
        $resposta = $this->agendar(2, null, 'tarde', ['telefone' => '(15) 99999-0000', 'observacoes' => 'Quero testar na estrada.']);

        $this->assertSame(201, $resposta->status, $resposta->corpo);
        $dados = $resposta->dadosJson()['dados'];
        $this->assertSame('pendente', $dados['status']);
        $this->assertSame('hilux-srx-4x4', $dados['modelo']['slug']);
        $this->assertSame('/api/agendamentos/' . $dados['id'], $resposta->cabecalhos['Location']);
        $this->assertNotNull($this->ultimaLinhaDoLog('agendamento.criado'));
    }

    public function testDataPassadaDomingoEModeloInexistenteDao422(): void
    {
        $this->assertSame('Escolha uma data a partir de amanhã.', $this->agendar(1, '2020-01-10')->dadosJson()['erro']['campos']['data']);

        $domingo = (new \DateTimeImmutable('next sunday'))->format('Y-m-d');
        $this->assertSame(422, $this->agendar(1, $domingo)->status);

        $resposta = $this->agendar(999);
        $this->assertSame(422, $resposta->status);
        $this->assertArrayHasKey('modelo_id', $resposta->dadosJson()['erro']['campos']);
    }

    public function testMesmoClienteNoMesmoHorarioDa409(): void
    {
        $data = $this->dataValida();
        $this->assertSame(201, $this->agendar(1, $data)->status);

        $resposta = $this->agendar(3, $data);
        $this->assertSame(409, $resposta->status);
        $this->assertSame('Você já tem um test-drive marcado nesse dia e período.', $resposta->dadosJson()['erro']['mensagem']);
    }

    public function testQuartoAgendamentoDoMesmoModeloNoPeriodoDa409(): void
    {
        $data = $this->dataValida(5);
        $this->assertSame(201, $this->agendar(3, $data)->status);

        foreach (['b', 'c', 'd'] as $i => $letra) {
            $this->novaSessao();
            $this->criarConta("{$letra}@teste.com");
            $this->entrarComo("{$letra}@teste.com");
            $esperado = $i < 2 ? 201 : 409;
            $this->assertSame($esperado, $this->agendar(3, $data)->status, "cliente {$letra}");
        }
    }

    public function testClienteNaoVeNemCancelaAgendamentoDeOutro(): void
    {
        $id = $this->agendar()->dadosJson()['dados']['id'];

        $this->novaSessao();
        $this->criarConta('outro@teste.com');
        $this->entrarComo('outro@teste.com');

        $this->assertSame(404, $this->api('GET', "/api/agendamentos/{$id}")->status);
        $this->assertSame(404, $this->api('PATCH', "/api/agendamentos/{$id}/status", ['status' => 'cancelado'])->status);
        $this->assertSame(0, $this->api('GET', '/api/agendamentos')->dadosJson()['meta']['total']);
    }

    public function testClienteCancelaOProprioMasNaoConfirma(): void
    {
        $id = $this->agendar()->dadosJson()['dados']['id'];

        $this->assertSame(409, $this->api('PATCH', "/api/agendamentos/{$id}/status", ['status' => 'confirmado'])->status);

        $resposta = $this->api('PATCH', "/api/agendamentos/{$id}/status", ['status' => 'cancelado']);
        $this->assertSame(200, $resposta->status);
        $this->assertSame('cancelado', $resposta->dadosJson()['dados']['status']);
    }

    public function testAdministradorConfirmaEMarcaComoRealizado(): void
    {
        $id = $this->agendar()->dadosJson()['dados']['id'];

        $this->novaSessao();
        $this->criarConta('chefe@teste.com', 'admin');
        $this->entrarComo('chefe@teste.com');

        $this->assertSame('confirmado', $this->api('PATCH', "/api/agendamentos/{$id}/status", ['status' => 'confirmado'])->dadosJson()['dados']['status']);
        $this->assertSame('realizado', $this->api('PATCH', "/api/agendamentos/{$id}/status", ['status' => 'realizado'])->dadosJson()['dados']['status']);
        $this->assertSame(409, $this->api('PATCH', "/api/agendamentos/{$id}/status", ['status' => 'cancelado'])->status);
    }

    public function testRemarcarAlteraSoOsCamposEnviados(): void
    {
        $criado = $this->agendar(1, null, 'manha', ['observacoes' => 'Original'])->dadosJson()['dados'];
        $novaData = $this->dataValida(10);

        $resposta = $this->api('PATCH', '/api/agendamentos/' . $criado['id'], ['data' => $novaData, 'periodo' => 'tarde']);

        $this->assertSame(200, $resposta->status, $resposta->corpo);
        $dados = $resposta->dadosJson()['dados'];
        $this->assertSame([$novaData, 'tarde', 'Original'], [$dados['data'], $dados['periodo'], $dados['observacoes']]);
    }

    public function testListagemPaginadaEFiltroPorStatus(): void
    {
        foreach ([3, 4, 6] as $dias) {
            $this->assertSame(201, $this->agendar(1, $this->dataValida($dias + 7))->status);
        }

        $pagina = $this->api('GET', '/api/agendamentos?por_pagina=2&pagina=2')->dadosJson();
        $this->assertCount(1, $pagina['dados']);
        $this->assertSame(['pagina' => 2, 'por_pagina' => 2, 'total' => 3, 'total_paginas' => 2], $pagina['meta']);

        $this->assertSame(0, $this->api('GET', '/api/agendamentos?status=confirmado')->dadosJson()['meta']['total']);
    }

    public function testSoAdministradorExclui(): void
    {
        $id = $this->agendar()->dadosJson()['dados']['id'];
        $this->assertSame(403, $this->api('DELETE', "/api/agendamentos/{$id}")->status);

        $this->novaSessao();
        $this->criarConta('chefe2@teste.com', 'admin');
        $this->entrarComo('chefe2@teste.com');

        $this->assertSame(204, $this->api('DELETE', "/api/agendamentos/{$id}")->status);
        $this->assertSame(404, $this->api('GET', "/api/agendamentos/{$id}")->status);
    }

    public function testFormularioDoSiteCriaAgendamentoERedireciona(): void
    {
        $resposta = $this->formulario('/agendar', [
            'modelo_id' => '3', 'data' => $this->dataValida(4), 'periodo' => 'tarde', 'telefone' => '', 'observacoes' => '',
        ]);

        $this->assertSame(303, $resposta->status, $resposta->corpo);
        $this->assertSame('/meus-agendamentos', $resposta->cabecalhos['Location']);

        $pagina = $this->requisitar('GET', '/meus-agendamentos');
        $this->assertStringContainsString('GR Supra', $pagina->corpo);
        $this->assertStringContainsString('Test-drive agendado.', $pagina->corpo);
    }

    public function testFormularioComErroMostraMensagensEMantemOQueFoiDigitado(): void
    {
        $resposta = $this->formulario('/agendar', [
            'modelo_id' => '1', 'data' => '2020-01-01', 'periodo' => '', 'observacoes' => 'Manter este texto',
        ]);

        $this->assertSame(422, $resposta->status);
        $this->assertStringContainsString('Escolha uma data a partir de amanhã.', $resposta->corpo);
        $this->assertStringContainsString('id="erro-periodo"', $resposta->corpo);
        $this->assertStringContainsString('Manter este texto', $resposta->corpo);
    }
}
