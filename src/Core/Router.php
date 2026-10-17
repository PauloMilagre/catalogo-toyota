<?php

declare(strict_types=1);

namespace Toyota\Core;

/**
 * Associa método + URL a um método de controller.
 *
 * Parâmetros na URL usam chaves: /api/agendamentos/{id}. Cada nome de
 * parâmetro tem um formato fixo (id só aceita dígitos), então
 * /api/agendamentos/abc nem chega ao controller: é 404 aqui mesmo.
 *
 * Opções de cada rota:
 *   auth   => true      exige login
 *   perfil => 'admin'   exige login E esse perfil
 *   csrf   => false     dispensa o token CSRF (padrão: exigido fora do GET)
 */
final class Router
{
    private const FORMATOS = [
        'id'   => '\d+',
        'slug' => '[a-z0-9-]+',
    ];

    /** @var list<Rota> */
    private array $rotas = [];

    /** @param array{0: class-string, 1: string} $acao */
    public function get(string $padrao, array $acao, array $opcoes = []): void
    {
        $this->adicionar('GET', $padrao, $acao, $opcoes);
    }

    public function post(string $padrao, array $acao, array $opcoes = []): void
    {
        $this->adicionar('POST', $padrao, $acao, $opcoes);
    }

    public function put(string $padrao, array $acao, array $opcoes = []): void
    {
        $this->adicionar('PUT', $padrao, $acao, $opcoes);
    }

    public function patch(string $padrao, array $acao, array $opcoes = []): void
    {
        $this->adicionar('PATCH', $padrao, $acao, $opcoes);
    }

    public function delete(string $padrao, array $acao, array $opcoes = []): void
    {
        $this->adicionar('DELETE', $padrao, $acao, $opcoes);
    }

    /**
     * @param array{0: class-string, 1: string} $acao
     * @param array{auth?: bool, perfil?: string, csrf?: bool} $opcoes
     */
    public function adicionar(string $metodo, string $padrao, array $acao, array $opcoes = []): void
    {
        $regex = preg_replace_callback(
            '#\\\\\{(\w+)\\\\\}#',
            static fn (array $m): string => '(?P<' . $m[1] . '>' . (self::FORMATOS[$m[1]] ?? '[^/]+') . ')',
            preg_quote($padrao, '#')
        );

        $this->rotas[] = new Rota(
            $metodo,
            $padrao,
            '#^' . $regex . '$#',
            $acao,
            $opcoes['auth'] ?? false,
            $opcoes['perfil'] ?? null,
            $opcoes['csrf'] ?? ($metodo !== 'GET')
        );
    }

    /**
     * @return array{0: Rota, 1: array<string, string>}
     * @throws HttpException 404 se nenhuma URL bate, 405 se a URL existe com outro método
     */
    public function resolver(string $metodo, string $caminho): array
    {
        $permitidos = [];
        foreach ($this->rotas as $rota) {
            if (!preg_match($rota->regex, $caminho, $achados)) {
                continue;
            }
            if ($rota->metodo === $metodo) {
                $parametros = array_filter($achados, 'is_string', ARRAY_FILTER_USE_KEY);
                return [$rota, $parametros];
            }
            $permitidos[] = $rota->metodo;
        }

        if ($permitidos !== []) {
            throw new HttpException(405, 'Método não permitido para esta URL.', [], [
                'Allow' => implode(', ', array_unique($permitidos)),
            ]);
        }
        throw HttpException::naoEncontrado('Página ou recurso não encontrado.');
    }
}
