<?php

declare(strict_types=1);

namespace Toyota\Testes\Unitarios;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Toyota\Core\Avisos;
use Toyota\Core\Csrf;
use Toyota\Core\ErrorHandler;
use Toyota\Core\HttpException;
use Toyota\Core\Logger;
use Toyota\Core\Migrador;
use Toyota\Core\Paginacao;
use Toyota\Core\Request;
use Toyota\Core\SessaoMemoria;
use Toyota\Core\ValidacaoException;
use Toyota\Core\View;

/** Paginação, descoberta da pasta do site, separação do SQL e respostas de erro. */
final class InfraestruturaTest extends TestCase
{
    private string $log;

    protected function setUp(): void
    {
        $this->log = sys_get_temp_dir() . '/toyota-teste-' . bin2hex(random_bytes(4)) . '.log';
    }

    protected function tearDown(): void
    {
        if (is_file($this->log)) {
            unlink($this->log);
        }
    }

    private function tratador(bool $desenvolvimento): ErrorHandler
    {
        $sessao = new SessaoMemoria();
        $view = new View(dirname(__DIR__, 2) . '/src/Views', '', new Csrf($sessao), new Avisos($sessao));
        return new ErrorHandler(new Logger($this->log), $desenvolvimento, $view);
    }

    public function testPaginacaoCalculaDeslocamentoETotalDePaginas(): void
    {
        $paginacao = Paginacao::daRequisicao(new Request('GET', '/x', ['pagina' => '3', 'por_pagina' => '10']));

        $this->assertSame(20, $paginacao->offset());
        $this->assertSame(
            ['pagina' => 3, 'por_pagina' => 10, 'total' => 21, 'total_paginas' => 3],
            $paginacao->meta(21)
        );
        $this->assertSame(1, (new Paginacao())->meta(0)['total_paginas']);
    }

    public function testPaginacaoRecusaPorPaginaAcimaDoLimite(): void
    {
        $this->expectException(ValidacaoException::class);
        Paginacao::daRequisicao(new Request('GET', '/x', ['por_pagina' => '500']));
    }

    public function testDescobreAPastaDoSiteNoServidorEmbutidoENoXampp(): void
    {
        // php -S localhost:8000 -t public
        $this->assertSame(['', '/agendar'], Request::separarBase('/agendar', '/index.php'));
        // XAMPP, via .htaccess da raiz: http://localhost/catalogo-toyota/agendar
        $this->assertSame(
            ['/catalogo-toyota', '/agendar'],
            Request::separarBase('/catalogo-toyota/agendar', '/catalogo-toyota/public/index.php')
        );
        // XAMPP acessando public/ direto
        $this->assertSame(
            ['/catalogo-toyota/public', '/'],
            Request::separarBase('/catalogo-toyota/public/', '/catalogo-toyota/public/index.php')
        );
    }

    public function testMigradorSeparaComandosEIgnoraComentarios(): void
    {
        $sql = "-- comentário\nCREATE TABLE a (x INT);\n\n-- outro\nINSERT INTO a VALUES (1);\n";
        $this->assertSame(['CREATE TABLE a (x INT)', 'INSERT INTO a VALUES (1)'], Migrador::comandos($sql));
    }

    public function testErroInternoNaApiNaoMostraDetalhesEmProducaoMasRegistraNoLog(): void
    {
        $requisicao = new Request('GET', '/api/modelos');
        $resposta = $this->tratador(false)->resposta(new RuntimeException('senha do banco: segredo'), $requisicao);

        $this->assertSame(500, $resposta->status);
        $this->assertSame(
            ['erro' => ['status' => 500, 'mensagem' => 'Erro interno do servidor. Tente novamente mais tarde.']],
            $resposta->dadosJson()
        );
        $this->assertStringNotContainsString('segredo', $resposta->corpo);

        $linha = (string) file_get_contents($this->log);
        $this->assertMatchesRegularExpression('/^\[\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}\] ERROR erro\.interno /', $linha);
        $this->assertStringContainsString('segredo', $linha);
    }

    public function testErroInternoMostraDetalhesSoEmDesenvolvimento(): void
    {
        $resposta = $this->tratador(true)->resposta(new RuntimeException('falha X'), new Request('GET', '/api/modelos'));
        $this->assertSame('falha X', $resposta->dadosJson()['erro']['detalhes']['mensagem']);
    }

    public function testErroDeValidacaoNaApiTrazOsCampos(): void
    {
        $resposta = $this->tratador(false)->resposta(
            new ValidacaoException(['data' => 'Data inválida.']),
            new Request('POST', '/api/agendamentos')
        );

        $this->assertSame(422, $resposta->status);
        $this->assertSame(['data' => 'Data inválida.'], $resposta->dadosJson()['erro']['campos']);
    }

    public function testPaginaSemPermissaoMostraPaginaDeErroComMesmoCodigo(): void
    {
        $resposta = $this->tratador(false)->resposta(HttpException::proibido(), new Request('GET', '/admin/agendamentos'));

        $this->assertSame(403, $resposta->status);
        $this->assertStringContainsString('Erro 403', $resposta->corpo);
    }
}
