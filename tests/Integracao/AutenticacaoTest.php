<?php

declare(strict_types=1);

namespace Toyota\Testes\Integracao;

/** Cadastro, login, logout e limite de tentativas. */
final class AutenticacaoTest extends CasoDeTesteIntegracao
{
    public function testCadastroCriaClienteEGuardaSoOHashDaSenha(): void
    {
        $usuario = $this->criarConta('ana@teste.com');

        $this->assertSame('cliente', $usuario['perfil']);
        $hash = $this->pdo()->query('SELECT senha_hash FROM usuarios WHERE id = ' . (int) $usuario['id'])->fetchColumn();
        $this->assertStringStartsWith('$2y$12$', $hash, 'bcrypt com custo 12');
        $this->assertTrue(password_verify('senha1234', $hash));
        $this->assertNotNull($this->ultimaLinhaDoLog('usuario.criado'));
    }

    public function testCadastroIgnoraPerfilEnviadoPeloCliente(): void
    {
        $resposta = $this->requisitar('POST', '/api/auth/registro', [
            'nome' => 'Espertinho', 'email' => 'esperto@teste.com', 'senha' => 'senha1234', 'perfil' => 'admin',
        ]);

        $this->assertSame(201, $resposta->status);
        $this->assertSame('cliente', $resposta->dadosJson()['dados']['perfil']);
    }

    public function testCadastroComEmailRepetidoDa409(): void
    {
        $this->criarConta('bia@teste.com');
        $resposta = $this->requisitar('POST', '/api/auth/registro', [
            'nome' => 'Outra Bia', 'email' => 'BIA@teste.com', 'senha' => 'senha1234',
        ]);

        $this->assertSame(409, $resposta->status);
        $this->assertSame('E-mail já cadastrado.', $resposta->dadosJson()['erro']['campos']['email']);
    }

    public function testCadastroComDadosInvalidosDa422ComMensagemPorCampo(): void
    {
        $resposta = $this->requisitar('POST', '/api/auth/registro', ['nome' => 'Jo', 'email' => 'x', 'senha' => '123']);

        $this->assertSame(422, $resposta->status);
        $this->assertSame(['nome', 'email', 'senha'], array_keys($resposta->dadosJson()['erro']['campos']));
    }

    public function testLoginCorretoTrocaASessaoEDevolveTokenCsrf(): void
    {
        $this->criarConta('caio@teste.com');
        $resposta = $this->requisitar('POST', '/api/auth/login', ['email' => 'caio@teste.com', 'senha' => 'senha1234']);

        $this->assertSame(200, $resposta->status);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $resposta->dadosJson()['dados']['csrf_token']);
        $this->assertSame(1, $this->sessao->regeneracoes, 'o id da sessão é trocado no login');
        $this->assertStringContainsString('"usuario_id"', (string) $this->ultimaLinhaDoLog('login.sucesso'));
    }

    public function testLoginComSenhaErradaDa401ERegistraAFalha(): void
    {
        $this->criarConta('duda@teste.com');
        $resposta = $this->requisitar('POST', '/api/auth/login', ['email' => 'duda@teste.com', 'senha' => 'errada123']);

        $this->assertSame(401, $resposta->status);
        $this->assertSame('E-mail ou senha inválidos.', $resposta->dadosJson()['erro']['mensagem']);
        $this->assertStringContainsString('duda@teste.com', (string) $this->ultimaLinhaDoLog('login.falha'));
    }

    public function testEmailInexistenteDaAMesmaMensagemQueSenhaErrada(): void
    {
        $resposta = $this->requisitar('POST', '/api/auth/login', ['email' => 'ninguem@teste.com', 'senha' => 'qualquer1']);

        $this->assertSame(401, $resposta->status);
        $this->assertSame('E-mail ou senha inválidos.', $resposta->dadosJson()['erro']['mensagem']);
    }

    public function testSextaTentativaErradaSeguidaEhBloqueadaCom429(): void
    {
        $this->criarConta('edu@teste.com');
        for ($i = 1; $i <= 5; $i++) {
            $resposta = $this->requisitar('POST', '/api/auth/login', ['email' => 'edu@teste.com', 'senha' => "errada{$i}"], [], '10.9.9.9');
            $this->assertSame(401, $resposta->status);
        }

        // Nem a senha certa entra enquanto o IP estiver bloqueado.
        $resposta = $this->requisitar('POST', '/api/auth/login', ['email' => 'edu@teste.com', 'senha' => 'senha1234'], [], '10.9.9.9');
        $this->assertSame(429, $resposta->status);
        $this->assertGreaterThan(0, (int) $resposta->cabecalhos['Retry-After']);

        // Outro IP não é afetado.
        $resposta = $this->requisitar('POST', '/api/auth/login', ['email' => 'edu@teste.com', 'senha' => 'senha1234'], [], '10.1.1.1');
        $this->assertSame(200, $resposta->status);
    }

    public function testLogoutEncerraASessao(): void
    {
        $this->criarConta('fabi@teste.com');
        $this->entrarComo('fabi@teste.com');
        $this->assertSame(200, $this->api('GET', '/api/auth/eu')->status);

        $this->assertSame(204, $this->api('POST', '/api/auth/logout')->status);
        $this->assertSame(401, $this->api('GET', '/api/auth/eu')->status);
    }

    public function testLoginPeloSiteRedirecionaEMantemDestinoInterno(): void
    {
        $this->criarConta('gabi@teste.com');
        $resposta = $this->formulario('/entrar', [
            'email' => 'gabi@teste.com', 'senha' => 'senha1234', 'voltar' => '/agendar?modelo=gr-supra',
        ]);

        $this->assertSame(303, $resposta->status);
        $this->assertSame('/agendar?modelo=gr-supra', $resposta->cabecalhos['Location']);
    }

    public function testDestinoExternoAposLoginEhIgnorado(): void
    {
        $this->criarConta('hugo@teste.com');
        $resposta = $this->formulario('/entrar', [
            'email' => 'hugo@teste.com', 'senha' => 'senha1234', 'voltar' => '//site-falso.com/roubo',
        ]);

        $this->assertSame('/', $resposta->cabecalhos['Location']);
    }
}
