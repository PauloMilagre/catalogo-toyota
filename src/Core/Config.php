<?php

declare(strict_types=1);

namespace Toyota\Core;

use RuntimeException;

/**
 * Configuração da aplicação, lida do arquivo .env.
 *
 * Variáveis de ambiente reais têm prioridade sobre o .env. É assim que os
 * testes apontam para o banco de teste sem editar arquivo nenhum.
 * O .env fica fora do Git (.gitignore); o repositório só tem o .env.example.
 */
final class Config
{
    /** @param array<string, string> $valores */
    private function __construct(private array $valores)
    {
    }

    public static function carregar(string $arquivoEnv): self
    {
        $valores = [];
        if (is_file($arquivoEnv)) {
            $linhas = file($arquivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($linhas as $linha) {
                $linha = trim($linha);
                if ($linha === '' || str_starts_with($linha, '#') || !str_contains($linha, '=')) {
                    continue;
                }
                [$chave, $valor] = array_map('trim', explode('=', $linha, 2));
                $valores[$chave] = self::tirarAspas($valor);
            }
        }
        return new self($valores);
    }

    /** @param array<string, string> $valores */
    public static function deArray(array $valores): self
    {
        return new self($valores);
    }

    public function get(string $chave, ?string $padrao = null): ?string
    {
        $ambiente = getenv($chave);
        if ($ambiente !== false) {
            return $ambiente;
        }
        return $this->valores[$chave] ?? $padrao;
    }

    public function exigir(string $chave): string
    {
        $valor = $this->get($chave);
        if ($valor === null) {
            throw new RuntimeException("Configuração ausente: {$chave}. Confira o arquivo .env.");
        }
        return $valor;
    }

    public function bool(string $chave, bool $padrao = false): bool
    {
        $valor = $this->get($chave);
        if ($valor === null) {
            return $padrao;
        }
        return in_array(strtolower($valor), ['1', 'true', 'sim', 'on', 'yes'], true);
    }

    public function int(string $chave, int $padrao): int
    {
        $valor = $this->get($chave);
        return $valor !== null && is_numeric($valor) ? (int) $valor : $padrao;
    }

    /** Detalhes técnicos de erro só aparecem em desenvolvimento com debug ligado. */
    public function modoDesenvolvimento(): bool
    {
        return $this->get('APP_ENV', 'producao') === 'desenvolvimento' && $this->bool('APP_DEBUG');
    }

    private static function tirarAspas(string $valor): string
    {
        $tamanho = strlen($valor);
        if ($tamanho >= 2 && ($valor[0] === '"' || $valor[0] === "'") && $valor[$tamanho - 1] === $valor[0]) {
            return substr($valor, 1, -1);
        }
        return $valor;
    }
}
