<?php

declare(strict_types=1);

namespace Toyota\Testes\Unitarios;

use PHPUnit\Framework\TestCase;
use Toyota\Core\HttpException;
use Toyota\Core\Router;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/api/agendamentos/{id}', ['Controller', 'mostrar'], ['auth' => true]);
        $this->router->patch('/api/agendamentos/{id}', ['Controller', 'atualizar'], ['auth' => true]);
        $this->router->get('/modelos/{slug}', ['Controller', 'ficha']);
        $this->router->post('/api/auth/login', ['Controller', 'login'], ['csrf' => false]);
        $this->router->delete('/api/modelos/{id}', ['Controller', 'excluir'], ['perfil' => 'admin']);
    }

    private function status(string $metodo, string $caminho): int
    {
        try {
            $this->router->resolver($metodo, $caminho);
        } catch (HttpException $erro) {
            return $erro->status;
        }
        return 200;
    }

    public function testResolveRotaEExtraiParametros(): void
    {
        [$rota, $parametros] = $this->router->resolver('GET', '/api/agendamentos/42');

        $this->assertSame(['Controller', 'mostrar'], $rota->acao);
        $this->assertSame(['id' => '42'], $parametros);
    }

    public function testUrlInexistenteDa404(): void
    {
        $this->assertSame(404, $this->status('GET', '/nao-existe'));
    }

    public function testMetodoNaoPermitidoDa405ComCabecalhoAllow(): void
    {
        try {
            $this->router->resolver('DELETE', '/api/agendamentos/1');
            $this->fail('Era esperado 405.');
        } catch (HttpException $erro) {
            $this->assertSame(405, $erro->status);
            $this->assertSame('GET, PATCH', $erro->cabecalhos['Allow']);
        }
    }

    public function testParametroForaDoFormatoNaoCasa(): void
    {
        $this->assertSame(404, $this->status('GET', '/api/agendamentos/1 OR 1=1'));
        $this->assertSame(404, $this->status('GET', '/api/agendamentos/abc'));
        $this->assertSame(404, $this->status('GET', '/modelos/Supra<script>'));
    }

    public function testRegrasDeAcessoPadraoDasRotas(): void
    {
        [$ficha] = $this->router->resolver('GET', '/modelos/gr-supra');
        [$login] = $this->router->resolver('POST', '/api/auth/login');
        [$excluir] = $this->router->resolver('DELETE', '/api/modelos/1');

        $this->assertFalse($ficha->exigeLogin());
        $this->assertFalse($ficha->csrf, 'GET não exige token CSRF');
        $this->assertFalse($login->csrf, 'login da API dispensa CSRF de propósito');
        $this->assertTrue($excluir->csrf, 'DELETE exige CSRF por padrão');
        $this->assertTrue($excluir->exigeLogin(), 'exigir perfil implica exigir login');
        $this->assertSame('admin', $excluir->perfil);
    }
}
