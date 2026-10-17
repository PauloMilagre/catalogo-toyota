<?php

declare(strict_types=1);

namespace Toyota\Core;

use DateTimeImmutable;

/**
 * Registro de eventos da aplicação em logs/app.log.
 *
 * Formato de cada linha:
 *   [2026-10-16T14:03:22-03:00] INFO login.sucesso {"usuario_id":3,"ip":"127.0.0.1"}
 *
 * O contexto vai em JSON, que escapa quebras de linha: um valor digitado
 * pelo usuário (ex.: um e-mail com "\n") não consegue forjar linhas falsas
 * no log. Senhas nunca são registradas.
 */
final class Logger
{
    public function __construct(private string $arquivo)
    {
    }

    /** @param array<string, mixed> $contexto */
    public function info(string $evento, array $contexto = []): void
    {
        $this->registrar('INFO', $evento, $contexto);
    }

    /** @param array<string, mixed> $contexto */
    public function aviso(string $evento, array $contexto = []): void
    {
        $this->registrar('WARNING', $evento, $contexto);
    }

    /** @param array<string, mixed> $contexto */
    public function erro(string $evento, array $contexto = []): void
    {
        $this->registrar('ERROR', $evento, $contexto);
    }

    /** @param array<string, mixed> $contexto */
    private function registrar(string $nivel, string $evento, array $contexto): void
    {
        $linha = sprintf(
            "[%s] %s %s %s\n",
            (new DateTimeImmutable())->format(DATE_ATOM),
            $nivel,
            $evento,
            json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR)
        );

        $pasta = dirname($this->arquivo);
        if (!is_dir($pasta)) {
            mkdir($pasta, 0775, true);
        }
        file_put_contents($this->arquivo, $linha, FILE_APPEND | LOCK_EX);
    }
}
