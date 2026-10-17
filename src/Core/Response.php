<?php

declare(strict_types=1);

namespace Toyota\Core;

/** Resposta HTTP montada pelos controllers e enviada uma única vez no fim. */
final class Response
{
    /** @param array<string, string> $cabecalhos */
    public function __construct(
        public int $status = 200,
        public string $corpo = '',
        public array $cabecalhos = []
    ) {
    }

    public static function json(mixed $dados, int $status = 200, array $cabecalhos = []): self
    {
        $corpo = json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        return new self($status, $corpo, ['Content-Type' => 'application/json; charset=utf-8'] + $cabecalhos);
    }

    public static function html(string $html, int $status = 200, array $cabecalhos = []): self
    {
        return new self($status, $html, ['Content-Type' => 'text/html; charset=utf-8'] + $cabecalhos);
    }

    /** 303 See Other: depois de um POST, o navegador segue com GET (padrão PRG). */
    public static function redirecionar(string $url, int $status = 303): self
    {
        return new self($status, '', ['Location' => $url]);
    }

    public static function semConteudo(): self
    {
        return new self(204);
    }

    public function comCabecalho(string $nome, string $valor): self
    {
        $this->cabecalhos[$nome] = $valor;
        return $this;
    }

    public function enviar(): void
    {
        http_response_code($this->status);
        foreach ($this->cabecalhos as $nome => $valor) {
            header($nome . ': ' . $valor);
        }
        echo $this->corpo;
    }

    /** Usado nos testes para ler respostas JSON. */
    public function dadosJson(): mixed
    {
        return json_decode($this->corpo, true);
    }
}
