<?php

declare(strict_types=1);

namespace Toyota\Testes\Unitarios;

use PHPUnit\Framework\TestCase;
use Toyota\Core\Csrf;
use Toyota\Core\HttpException;
use Toyota\Core\SessaoMemoria;

final class CsrfTest extends TestCase
{
    private Csrf $csrf;

    protected function setUp(): void
    {
        $this->csrf = new Csrf(new SessaoMemoria());
    }

    private function recusado(mixed $token): bool
    {
        try {
            $this->csrf->validar($token);
            return false;
        } catch (HttpException $erro) {
            return $erro->status === 403;
        }
    }

    public function testTokenTem64CaracteresEEhEstavelNaMesmaSessao(): void
    {
        $token = $this->csrf->token();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        $this->assertSame($token, $this->csrf->token());
    }

    public function testTokenCorretoEhAceito(): void
    {
        $this->assertFalse($this->recusado($this->csrf->token()));
    }

    public function testTokenAusenteErradoOuDeOutroTipoEhRecusado(): void
    {
        $this->csrf->token();
        $this->assertTrue($this->recusado(null));
        $this->assertTrue($this->recusado(''));
        $this->assertTrue($this->recusado(str_repeat('0', 64)));
        $this->assertTrue($this->recusado(['array']));
    }

    public function testTokenDeOutraSessaoEhRecusado(): void
    {
        $tokenAlheio = (new Csrf(new SessaoMemoria()))->token();
        $this->csrf->token();
        $this->assertTrue($this->recusado($tokenAlheio));
    }

    public function testRenovarInvalidaOTokenAntigo(): void
    {
        $antigo = $this->csrf->token();
        $novo = $this->csrf->renovar();

        $this->assertNotSame($antigo, $novo);
        $this->assertTrue($this->recusado($antigo));
        $this->assertFalse($this->recusado($novo));
    }
}
