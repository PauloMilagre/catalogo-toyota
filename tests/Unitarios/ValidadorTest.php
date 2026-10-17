<?php

declare(strict_types=1);

namespace Toyota\Testes\Unitarios;

use PHPUnit\Framework\TestCase;
use Toyota\Core\ValidacaoException;
use Toyota\Core\Validador;

/** Validação e sanitização de entrada (Core\Validador). */
final class ValidadorTest extends TestCase
{
    private function erros(array $entrada, array $regras): array
    {
        try {
            Validador::validar($entrada, $regras);
        } catch (ValidacaoException $erro) {
            $this->assertSame(422, $erro->status);
            return $erro->campos;
        }
        $this->fail('Era esperada uma ValidacaoException.');
    }

    public function testAceitaDadosValidosELimpaEspacosDasPontas(): void
    {
        $dados = Validador::validar(
            ['nome' => '  Paulo Silva  ', 'idade' => '30', 'periodo' => 'tarde'],
            [
                'nome'    => ['obrigatorio', 'texto', 'min:3', 'max:100'],
                'idade'   => ['obrigatorio', 'inteiro', 'min:18'],
                'periodo' => ['obrigatorio', 'em:manha,tarde'],
            ]
        );

        $this->assertSame(['nome' => 'Paulo Silva', 'idade' => 30, 'periodo' => 'tarde'], $dados);
    }

    public function testCampoObrigatorioAusenteOuSoComEspacosEhRecusado(): void
    {
        $erros = $this->erros(['nome' => '   '], [
            'nome'  => ['obrigatorio', 'texto'],
            'email' => ['obrigatorio', 'email'],
        ]);

        $this->assertSame(['nome' => 'Campo obrigatório.', 'email' => 'Campo obrigatório.'], $erros);
    }

    public function testRecusaTiposErrados(): void
    {
        $erros = $this->erros(
            ['nome' => ['vetor', 'no lugar', 'de texto'], 'quantidade' => '3.5', 'ativo' => 'talvez'],
            [
                'nome'       => ['obrigatorio', 'texto'],
                'quantidade' => ['obrigatorio', 'inteiro'],
                'ativo'      => ['obrigatorio', 'booleano'],
            ]
        );

        $this->assertSame('Deve ser um texto.', $erros['nome']);
        $this->assertSame('Deve ser um número inteiro.', $erros['quantidade']);
        $this->assertSame('Deve ser verdadeiro ou falso.', $erros['ativo']);
    }

    public function testRespeitaTamanhoMinimoEMaximo(): void
    {
        $erros = $this->erros(
            ['curto' => 'ab', 'longo' => str_repeat('x', 401), 'numero' => 0],
            [
                'curto'  => ['obrigatorio', 'texto', 'min:3'],
                'longo'  => ['obrigatorio', 'texto', 'max:400'],
                'numero' => ['obrigatorio', 'inteiro', 'min:1'],
            ]
        );

        $this->assertSame('Deve ter pelo menos 3 caracteres.', $erros['curto']);
        $this->assertSame('Deve ter no máximo 400 caracteres.', $erros['longo']);
        $this->assertSame('Deve ser no mínimo 1.', $erros['numero']);
    }

    public function testTamanhoContaCaracteresENaoBytes(): void
    {
        // "ção" tem 3 caracteres e 5 bytes em UTF-8
        $dados = Validador::validar(['t' => 'ção'], ['t' => ['obrigatorio', 'texto', 'max:3']]);
        $this->assertSame('ção', $dados['t']);
    }

    public function testEmailInvalidoEhRecusadoEValidoFicaMinusculo(): void
    {
        $erros = $this->erros(['email' => 'paulo@'], ['email' => ['obrigatorio', 'email']]);
        $this->assertSame('E-mail inválido.', $erros['email']);

        $dados = Validador::validar(['email' => 'Paulo@UNESP.br'], ['email' => ['obrigatorio', 'email']]);
        $this->assertSame('paulo@unesp.br', $dados['email']);
    }

    public function testDataInexistenteOuEmOutroFormatoEhRecusada(): void
    {
        $regras = ['data' => ['obrigatorio', 'data']];

        $this->assertArrayHasKey('data', $this->erros(['data' => '2026-02-30'], $regras));
        $this->assertArrayHasKey('data', $this->erros(['data' => '30/10/2026'], $regras));
        $this->assertSame(['data' => '2026-10-30'], Validador::validar(['data' => '2026-10-30'], $regras));
    }

    public function testCamposNaoDeclaradosSaoDescartados(): void
    {
        $dados = Validador::validar(
            ['nome' => 'Cliente', 'perfil' => 'admin', 'id' => 1],
            ['nome' => ['obrigatorio', 'texto']]
        );

        $this->assertSame(['nome' => 'Cliente'], $dados);
    }

    public function testRemoveCaracteresDeControleEPadronizaQuebrasDeLinha(): void
    {
        $dados = Validador::validar(
            ['obs' => "linha 1\r\nlinha 2\x00\x07"],
            ['obs' => ['obrigatorio', 'texto']]
        );

        $this->assertSame("linha 1\nlinha 2", $dados['obs']);
    }

    public function testCampoOpcionalVazioViraNull(): void
    {
        $dados = Validador::validar(['telefone' => ''], ['telefone' => ['opcional', 'texto', 'max:20']]);
        $this->assertSame(['telefone' => null], $dados);
    }

    public function testSenhaSemNumeroEhRecusada(): void
    {
        $erros = $this->erros(['senha' => 'somenteletras'], ['senha' => ['obrigatorio', 'texto', 'min:8', 'senha_forte']]);
        $this->assertSame('A senha precisa ter letras e números.', $erros['senha']);
    }
}
