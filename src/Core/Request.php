<?php

declare(strict_types=1);

namespace Toyota\Core;

/**
 * Requisição HTTP já interpretada: método, caminho, consulta, corpo e cabeçalhos.
 *
 * O caminho é sempre relativo à aplicação ("/agendar"), não importa se o
 * site roda em http://localhost:8000/ ou em http://localhost/catalogo-toyota/.
 */
final class Request
{
    /**
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $corpo
     * @param array<string, string> $cabecalhos chaves em minúsculas
     */
    public function __construct(
        public readonly string $metodo,
        public readonly string $caminho,
        public readonly array $query = [],
        public readonly array $corpo = [],
        public readonly array $cabecalhos = [],
        public readonly string $ip = '127.0.0.1',
        public readonly string $base = '',
        public readonly bool $corpoInvalido = false
    ) {
    }

    public static function capturar(): self
    {
        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        [$base, $caminho] = self::separarBase($uri, (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        $cabecalhos = [];
        foreach ($_SERVER as $chave => $valor) {
            if (str_starts_with($chave, 'HTTP_')) {
                $cabecalhos[strtolower(str_replace('_', '-', substr($chave, 5)))] = (string) $valor;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $cabecalhos['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        $corpo = $_POST;
        $corpoInvalido = false;
        if (str_contains($cabecalhos['content-type'] ?? '', 'application/json')) {
            $bruto = (string) file_get_contents('php://input');
            $decodificado = json_decode($bruto, true);
            if ($bruto !== '' && !is_array($decodificado)) {
                $corpoInvalido = true;
                $decodificado = [];
            }
            $corpo = $decodificado ?? [];
        }

        return new self(
            $metodo,
            $caminho,
            $_GET,
            $corpo,
            $cabecalhos,
            (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
            $base,
            $corpoInvalido
        );
    }

    /**
     * Descobre em que pasta a aplicação está publicada.
     * Com o servidor embutido do PHP (-t public) a base é "". No XAMPP, a
     * base é "/catalogo-toyota" (o .htaccess da raiz repassa tudo a public/)
     * ou "/catalogo-toyota/public" se a pessoa acessar public/ direto.
     *
     * @return array{0: string, 1: string}
     */
    public static function separarBase(string $uri, string $script): array
    {
        $pasta = rtrim(str_replace('\\', '/', dirname($script)), '/');
        $candidatas = [$pasta];
        if (str_ends_with($pasta, '/public')) {
            $candidatas[] = substr($pasta, 0, -strlen('/public'));
        }
        foreach ($candidatas as $base) {
            if ($base !== '' && ($uri === $base || str_starts_with($uri, $base . '/'))) {
                return [$base, self::normalizar(substr($uri, strlen($base)))];
            }
        }
        return ['', self::normalizar($uri)];
    }

    private static function normalizar(string $caminho): string
    {
        $caminho = '/' . trim($caminho, '/');
        return $caminho === '/index.php' ? '/' : $caminho;
    }

    public function ehApi(): bool
    {
        return $this->caminho === '/api' || str_starts_with($this->caminho, '/api/');
    }

    public function cabecalho(string $nome): ?string
    {
        return $this->cabecalhos[strtolower($nome)] ?? null;
    }

    public function corpoEmJson(): bool
    {
        return str_contains($this->cabecalho('content-type') ?? '', 'application/json');
    }

    public function entrada(string $chave, mixed $padrao = null): mixed
    {
        return $this->corpo[$chave] ?? $padrao;
    }

    public function consulta(string $chave, mixed $padrao = null): mixed
    {
        return $this->query[$chave] ?? $padrao;
    }

    /** Caminho com a query string, usado para voltar à página depois do login. */
    public function caminhoCompleto(): string
    {
        return $this->query === [] ? $this->caminho : $this->caminho . '?' . http_build_query($this->query);
    }
}
