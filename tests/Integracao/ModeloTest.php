<?php

declare(strict_types=1);

namespace Toyota\Testes\Integracao;

/** Catálogo público e CRUD de modelos pelo administrador. */
final class ModeloTest extends CasoDeTesteIntegracao
{
    private function novoModelo(array $mudancas = []): array
    {
        return $mudancas + [
            'slug' => 'yaris-cross', 'nome' => 'Yaris Cross', 'categoria' => 'SUV compacto',
            'preco' => '150000.00', 'motor' => '1.5 flex', 'potencia_cv' => 122,
            'cambio' => 'Automático CVT', 'consumo_km_l' => 12.4, 'porta_malas_l' => 440,
        ];
    }

    private function entrarComoAdmin(): void
    {
        $this->criarConta('admin@teste.com', 'admin');
        $this->entrarComo('admin@teste.com');
    }

    public function testCatalogoESuaApiMostramOsModelosAtivos(): void
    {
        $pagina = $this->requisitar('GET', '/');
        $this->assertSame(200, $pagina->status);
        foreach (['Corolla Altis Hybrid', 'Hilux SRX 4x4', 'GR Supra'] as $nome) {
            $this->assertStringContainsString($nome, $pagina->corpo);
        }

        $api = $this->requisitar('GET', '/api/modelos')->dadosJson();
        $this->assertSame(3, $api['meta']['total']);
    }

    public function testFichaDoModeloVemDoBanco(): void
    {
        $pagina = $this->requisitar('GET', '/modelos/hilux-srx-4x4');

        $this->assertSame(200, $pagina->status);
        $this->assertStringContainsString('Capacidade de carga', $pagina->corpo);
        $this->assertStringContainsString('R$ 330.000', $pagina->corpo);
    }

    public function testAdministradorCriaAtualizaEExcluiModelo(): void
    {
        $this->entrarComoAdmin();

        $criado = $this->api('POST', '/api/modelos', $this->novoModelo());
        $this->assertSame(201, $criado->status, $criado->corpo);
        $id = $criado->dadosJson()['dados']['id'];
        $this->assertNotNull($this->ultimaLinhaDoLog('modelo.criado'));

        $atualizado = $this->api('PUT', "/api/modelos/{$id}", $this->novoModelo(['preco' => 155000, 'ativo' => false]));
        $this->assertSame(200, $atualizado->status, $atualizado->corpo);
        $this->assertSame([155000.0, false], [
            (float) $atualizado->dadosJson()['dados']['preco'],
            $atualizado->dadosJson()['dados']['ativo'],
        ]);

        // Inativo some do catálogo público.
        $this->assertSame(404, $this->requisitar('GET', '/modelos/yaris-cross')->status);

        $this->assertSame(204, $this->api('DELETE', "/api/modelos/{$id}")->status);
    }

    public function testSlugRepetidoDa409(): void
    {
        $this->entrarComoAdmin();
        $resposta = $this->api('POST', '/api/modelos', $this->novoModelo(['slug' => 'gr-supra']));

        $this->assertSame(409, $resposta->status);
    }

    public function testDadosInvalidosDoModeloDao422(): void
    {
        $this->entrarComoAdmin();
        $resposta = $this->api('POST', '/api/modelos', $this->novoModelo([
            'slug' => 'Com Espaço', 'preco' => 'caro', 'potencia_cv' => 0,
        ]));

        $this->assertSame(422, $resposta->status);
        $this->assertSame(['slug', 'preco', 'potencia_cv'], array_keys($resposta->dadosJson()['erro']['campos']));
    }

    public function testModeloComAgendamentoNaoPodeSerExcluido(): void
    {
        $this->criarConta('cli@teste.com');
        $this->entrarComo('cli@teste.com');
        $this->assertSame(201, $this->agendar(1)->status);

        $this->novaSessao();
        $this->entrarComoAdmin();
        $resposta = $this->api('DELETE', '/api/modelos/1');

        $this->assertSame(409, $resposta->status);
    }
}
