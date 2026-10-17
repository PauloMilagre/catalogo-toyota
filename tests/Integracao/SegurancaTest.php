<?php

declare(strict_types=1);

namespace Toyota\Testes\Integracao;

/**
 * Testes de segurança exigidos no enunciado (seção 6 e critério C2).
 * Cada teste tenta um ataque e confere que ele não funciona.
 * O passo a passo e as evidências estão em docs/testes-de-seguranca.md.
 */
final class SegurancaTest extends CasoDeTesteIntegracao
{
    // ----- 1. SQL Injection --------------------------------------------------

    public function testSqlInjectionNoLoginNaoAutentica(): void
    {
        $this->criarConta('vitima@teste.com');

        // Clássico "' OR '1'='1": se o SQL fosse montado colando texto, entraria sem senha.
        $porEmail = $this->requisitar('POST', '/api/auth/login', ['email' => "' OR '1'='1' -- ", 'senha' => 'x']);
        $porSenha = $this->requisitar('POST', '/api/auth/login', ['email' => 'vitima@teste.com', 'senha' => "' OR '1'='1"]);

        $this->assertSame(422, $porEmail->status, 'recusado já na validação do formato de e-mail');
        $this->assertSame(401, $porSenha->status, 'a senha é comparada com o hash, nunca entra no SQL');
        $this->assertNull($this->sessao->get('usuario_id'));
    }

    public function testSqlInjectionNaUrlENoFiltroNaoAlteraNemVazaDados(): void
    {
        $this->criarConta('cli@teste.com');
        $this->entrarComo('cli@teste.com');

        $this->assertSame(404, $this->api('GET', "/api/agendamentos/1 OR 1=1")->status);
        $this->assertSame(422, $this->api('GET', "/api/agendamentos?status=pendente' OR '1'='1")->status);

        // Texto com SQL vira dado comum, gravado exatamente como foi digitado.
        $ataque = "'); DROP TABLE agendamentos; --";
        $criado = $this->agendar(1, null, 'manha', ['observacoes' => $ataque]);
        $this->assertSame(201, $criado->status);
        $this->assertSame($ataque, $criado->dadosJson()['dados']['observacoes']);
        $this->assertSame(1, (int) $this->pdo()->query('SELECT COUNT(*) FROM agendamentos')->fetchColumn());
    }

    // ----- 2. XSS -------------------------------------------------------------

    public function testXssRefletidoNoFormularioDeCadastroEhNeutralizado(): void
    {
        // O nome volta para a tela porque o e-mail está errado: é o ponto de reflexão.
        $resposta = $this->formulario('/cadastro', [
            'nome' => '<script>alert("xss")</script>', 'email' => 'invalido', 'senha' => 'senha1234',
        ]);

        $this->assertSame(422, $resposta->status);
        $this->assertStringNotContainsString('<script>alert', $resposta->corpo);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $resposta->corpo);
    }

    public function testXssRefletidoNoParametroDaUrlEhNeutralizado(): void
    {
        $resposta = $this->requisitar('GET', '/entrar?voltar=/"><script>alert(1)</script>');

        $this->assertSame(200, $resposta->status);
        $this->assertStringNotContainsString('<script>alert(1)', $resposta->corpo);
    }

    public function testXssGravadoNasObservacoesEhNeutralizadoNoPainelDoAdmin(): void
    {
        $this->criarConta('cli@teste.com');
        $this->entrarComo('cli@teste.com');
        $this->agendar(1, null, 'manha', ['observacoes' => '<img src=x onerror=alert(1)>']);

        $this->novaSessao();
        $this->criarConta('admin@teste.com', 'admin');
        $this->entrarComo('admin@teste.com');
        $painel = $this->requisitar('GET', '/admin/agendamentos');

        $this->assertStringNotContainsString('<img src=x', $painel->corpo);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $painel->corpo);
        $this->assertStringContainsString("script-src 'none'", $painel->cabecalhos['Content-Security-Policy']);
    }

    // ----- 3. CSRF ------------------------------------------------------------

    public function testFormularioSemTokenCsrfEhRecusado(): void
    {
        $this->criarConta('cli@teste.com');
        $this->entrarComo('cli@teste.com');

        $semToken = $this->requisitar('POST', '/agendar', [
            'modelo_id' => '1', 'data' => $this->dataValida(), 'periodo' => 'manha',
        ]);
        $tokenFalso = $this->requisitar('POST', '/agendar', [
            'modelo_id' => '1', 'data' => $this->dataValida(), 'periodo' => 'manha', '_csrf' => str_repeat('a', 64),
        ]);

        $this->assertSame(403, $semToken->status);
        $this->assertSame(403, $tokenFalso->status);
        $this->assertSame(0, (int) $this->pdo()->query('SELECT COUNT(*) FROM agendamentos')->fetchColumn());
        $this->assertNotNull($this->ultimaLinhaDoLog('csrf.invalido'));
    }

    public function testApiSemCabecalhoCsrfEhRecusada(): void
    {
        $this->criarConta('cli@teste.com');
        $this->entrarComo('cli@teste.com');
        $this->tokenCsrf = null; // simula um site de terceiros, que não conhece o token

        $this->assertSame(403, $this->agendar()->status);
    }

    // ----- 4. Acesso sem login e com perfil insuficiente --------------------

    public function testRotasProtegidasSemLogin(): void
    {
        $this->assertSame(401, $this->requisitar('GET', '/api/agendamentos')->status);
        $this->assertSame(401, $this->requisitar('POST', '/api/modelos', [])->status);

        $pagina = $this->requisitar('GET', '/meus-agendamentos');
        $this->assertSame(303, $pagina->status);
        $this->assertSame('/entrar?voltar=%2Fmeus-agendamentos', $pagina->cabecalhos['Location']);
    }

    public function testClienteNaoAcessaAreaDoAdministrador(): void
    {
        $this->criarConta('cli@teste.com');
        $this->entrarComo('cli@teste.com');

        $this->assertSame(403, $this->requisitar('GET', '/admin/agendamentos')->status);
        $this->assertSame(403, $this->api('POST', '/api/modelos', ['slug' => 'x'])->status);
        $this->assertSame(403, $this->api('DELETE', '/api/modelos/1')->status);
        $this->assertStringContainsString('"perfil":"cliente"', (string) $this->ultimaLinhaDoLog('acesso.negado'));
    }

    public function testPerfilRetiradoValeNaHora(): void
    {
        $admin = $this->criarConta('ex-admin@teste.com', 'admin');
        $this->entrarComo('ex-admin@teste.com');
        $this->assertSame(200, $this->requisitar('GET', '/admin/agendamentos')->status);

        $this->pdo()->exec("UPDATE usuarios SET perfil = 'cliente' WHERE id = " . (int) $admin['id']);
        $this->assertSame(403, $this->requisitar('GET', '/admin/agendamentos')->status);
    }

    // ----- 5. Sessão e token reutilizados -----------------------------------

    public function testSessaoEncerradaNaoPodeSerReutilizada(): void
    {
        $this->criarConta('cli@teste.com');
        $this->entrarComo('cli@teste.com');
        $tokenAntigo = $this->tokenCsrf;
        $this->api('POST', '/api/auth/logout');

        // Mesmo "cookie" (a mesma sessão) e o token de antes do logout.
        $this->tokenCsrf = $tokenAntigo;
        $this->assertSame(401, $this->api('GET', '/api/agendamentos')->status);
        $this->assertSame(401, $this->agendar()->status);
    }

    public function testSessaoParadaPorMaisDeTrintaMinutosExpira(): void
    {
        $this->criarConta('cli@teste.com');
        $this->entrarComo('cli@teste.com');
        $this->sessao->set('ultimo_acesso', time() - 31 * 60);

        $this->assertSame(401, $this->api('GET', '/api/agendamentos')->status);
    }

    // ----- Outros -------------------------------------------------------------

    public function testRespostasLevamCabecalhosDeSeguranca(): void
    {
        $resposta = $this->requisitar('GET', '/');

        $this->assertSame('nosniff', $resposta->cabecalhos['X-Content-Type-Options']);
        $this->assertSame('DENY', $resposta->cabecalhos['X-Frame-Options']);
        $this->assertStringContainsString("frame-ancestors 'none'", $resposta->cabecalhos['Content-Security-Policy']);
    }

    public function testApiRecusaCorpoQueNaoSejaJson(): void
    {
        $resposta = $this->requisitar('POST', '/api/auth/login', ['email' => 'a@b.com'], ['content-type' => 'text/plain']);
        $this->assertSame(415, $resposta->status);
    }
}
