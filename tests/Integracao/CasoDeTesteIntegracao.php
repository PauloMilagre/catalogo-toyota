<?php

declare(strict_types=1);

namespace Toyota\Testes\Integracao;

use DateTimeImmutable;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Toyota\Core\Config;
use Toyota\Core\Csrf;
use Toyota\Core\Database;
use Toyota\Core\Fabrica;
use Toyota\Core\Migrador;
use Toyota\Core\Request;
use Toyota\Core\Response;
use Toyota\Core\SessaoMemoria;

/**
 * Base dos testes de integração: a aplicação inteira roda de verdade
 * (rotas, controllers, regras e MySQL), só que sem servidor web.
 *
 * Usa um banco separado (DB_NAME_TESTE no .env), apagado e recriado pelas
 * migrações no início da execução. O banco principal nunca é tocado.
 */
abstract class CasoDeTesteIntegracao extends TestCase
{
    private static ?Config $config = null;
    private static ?string $erroBanco = null;
    private static bool $preparado = false;

    protected SessaoMemoria $sessao;
    protected ?string $tokenCsrf = null;

    public static function setUpBeforeClass(): void
    {
        if (self::$preparado) {
            return;
        }
        self::$preparado = true;

        $doArquivo = Config::carregar(self::raiz() . '/.env');
        $principal = $doArquivo->get('DB_NAME', 'catalogo_toyota');
        $teste = $doArquivo->get('DB_NAME_TESTE', 'catalogo_toyota_teste');

        if ($teste === $principal) {
            self::$erroBanco = 'DB_NAME_TESTE não pode ser igual a DB_NAME (o banco de teste é apagado).';
            return;
        }

        putenv('DB_NAME=' . $teste);
        self::$config = Config::carregar(self::raiz() . '/.env');

        try {
            (new Migrador(self::$config, self::raiz() . '/database/migrations'))->migrar(true);
        } catch (PDOException $erro) {
            self::$erroBanco = $erro->getMessage();
        }
    }

    protected function setUp(): void
    {
        if (self::$erroBanco !== null) {
            $this->markTestSkipped('Banco de teste indisponível: ' . self::$erroBanco);
        }

        // Cada teste começa com o banco no estado inicial das migrações.
        $pdo = $this->pdo();
        $pdo->exec('DELETE FROM agendamentos');
        $pdo->exec('DELETE FROM usuarios');
        $pdo->exec('DELETE FROM tentativas_login');
        $pdo->exec('DELETE FROM modelos WHERE id > 3');
        $pdo->exec('UPDATE modelos SET ativo = 1');

        $this->novaSessao();
    }

    protected static function raiz(): string
    {
        return dirname(__DIR__, 2);
    }

    protected function pdo(): PDO
    {
        static $pdo = null;
        return $pdo ??= Database::conectar(self::$config);
    }

    /** Simula outro navegador: sessão nova, sem cookie. */
    protected function novaSessao(): void
    {
        $this->sessao = new SessaoMemoria();
        $this->tokenCsrf = null;
    }

    /**
     * Faz uma requisição à aplicação, como se viesse do navegador.
     * Cada chamada monta uma aplicação nova, como acontece no PHP real.
     *
     * @param array<string, mixed>  $corpo
     * @param array<string, string> $cabecalhos
     */
    protected function requisitar(
        string $metodo,
        string $url,
        array $corpo = [],
        array $cabecalhos = [],
        string $ip = '10.0.0.1'
    ): Response {
        [$caminho, $consulta] = array_pad(explode('?', $url, 2), 2, '');
        parse_str($consulta, $query);

        $cabecalhos = array_change_key_case($cabecalhos);
        if (str_starts_with($caminho, '/api/') && in_array($metodo, ['POST', 'PUT', 'PATCH'], true)) {
            $cabecalhos += ['content-type' => 'application/json'];
        }

        $app = Fabrica::criarApp(self::raiz(), fn () => $this->sessao, self::$config);
        return $app->handle(new Request($metodo, $caminho, $query, $corpo, $cabecalhos, $ip));
    }

    /** Requisição à API já com o token CSRF da sessão atual. */
    protected function api(string $metodo, string $url, array $corpo = [], string $ip = '10.0.0.1'): Response
    {
        $cabecalhos = $this->tokenCsrf !== null ? ['x-csrf-token' => $this->tokenCsrf] : [];
        return $this->requisitar($metodo, $url, $corpo, $cabecalhos, $ip);
    }

    /** Envio de formulário HTML com o campo _csrf preenchido. */
    protected function formulario(string $url, array $campos): Response
    {
        $campos['_csrf'] = (new Csrf($this->sessao))->token();
        return $this->requisitar('POST', $url, $campos);
    }

    /** Cria a conta direto pela API e devolve os dados. */
    protected function criarConta(string $email, string $perfil = 'cliente', string $senha = 'senha1234'): array
    {
        $resposta = $this->requisitar('POST', '/api/auth/registro', [
            'nome'  => 'Pessoa ' . strtok($email, '@'),
            'email' => $email,
            'senha' => $senha,
        ]);
        $this->assertSame(201, $resposta->status, $resposta->corpo);
        $usuario = $resposta->dadosJson()['dados'];

        if ($perfil === 'admin') {
            $this->pdo()->prepare("UPDATE usuarios SET perfil = 'admin' WHERE id = ?")->execute([$usuario['id']]);
            $usuario['perfil'] = 'admin';
        }
        return $usuario;
    }

    /** Faz login pela API nesta sessão e guarda o token CSRF. */
    protected function entrarComo(string $email, string $senha = 'senha1234'): void
    {
        $resposta = $this->requisitar('POST', '/api/auth/login', ['email' => $email, 'senha' => $senha]);
        $this->assertSame(200, $resposta->status, $resposta->corpo);
        $this->tokenCsrf = $resposta->dadosJson()['dados']['csrf_token'];
    }

    /** Uma data válida para agendamento: N dias à frente, pulando domingo. */
    protected function dataValida(int $dias = 3): string
    {
        $data = new DateTimeImmutable("+{$dias} days");
        if ($data->format('N') === '7') {
            $data = $data->modify('+1 day');
        }
        return $data->format('Y-m-d');
    }

    protected function agendar(int $modeloId = 1, ?string $data = null, string $periodo = 'manha', array $extra = []): Response
    {
        return $this->api('POST', '/api/agendamentos', $extra + [
            'modelo_id' => $modeloId,
            'data'      => $data ?? $this->dataValida(),
            'periodo'   => $periodo,
        ]);
    }

    protected function ultimaLinhaDoLog(string $evento): ?string
    {
        $arquivo = self::raiz() . '/logs/teste.log';
        $linhas = is_file($arquivo) ? file($arquivo, FILE_IGNORE_NEW_LINES) : [];
        foreach (array_reverse($linhas ?: []) as $linha) {
            if (str_contains($linha, ' ' . $evento . ' ')) {
                return $linha;
            }
        }
        return null;
    }
}
