<?php

declare(strict_types=1);

namespace Toyota\Core;

use DateTimeImmutable;
use Throwable;

/**
 * Monta as páginas HTML a partir dos arquivos de src/Views.
 *
 * Dentro de um template, $this é esta classe, então os templates usam
 * $this->e(...) para escapar qualquer valor antes de imprimir. É o que
 * impede XSS: "<script>" vira "&lt;script&gt;" e aparece como texto.
 */
final class View
{
    /** @var array<string, mixed> variáveis disponíveis em todos os templates */
    private array $globais = ['usuarioAtual' => null];

    private string $caminhoAtual = '/';

    public function __construct(
        private string $diretorio,
        private string $base,
        private Csrf $csrf,
        private Avisos $avisos
    ) {
    }

    /** Dados da requisição atual, definidos pelo App antes de chamar o controller. */
    public function contexto(Request $requisicao, ?array $usuarioAtual): void
    {
        $this->base = $requisicao->base;
        $this->caminhoAtual = $requisicao->caminho;
        $this->globais['usuarioAtual'] = $usuarioAtual;
    }

    /** @param array<string, mixed> $variaveis */
    public function render(string $template, array $variaveis = [], int $status = 200): Response
    {
        $conteudo = $this->incluir($template, $variaveis);
        $pagina = $this->incluir('layout', $variaveis + ['conteudo' => $conteudo]);
        return Response::html($pagina, $status);
    }

    /** @param array<string, mixed> $variaveis */
    public function parcial(string $template, array $variaveis = []): string
    {
        return $this->incluir('partials/' . $template, $variaveis);
    }

    /** @param array<string, mixed> $variaveis */
    private function incluir(string $template, array $variaveis): string
    {
        $arquivoDoTemplate = $this->diretorio . '/' . $template . '.php';
        extract($this->globais + $variaveis, EXTR_SKIP);
        ob_start();
        try {
            require $arquivoDoTemplate;
            return (string) ob_get_clean();
        } catch (Throwable $erro) {
            ob_end_clean();
            throw $erro;
        }
    }

    // ---------------------------------------------------------------------
    // Funções usadas dentro dos templates
    // ---------------------------------------------------------------------

    public function e(mixed $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** @param array<string, scalar> $query */
    public function url(string $caminho, array $query = []): string
    {
        $url = $this->base . $caminho;
        return $query === [] ? $url : $url . '?' . http_build_query($query);
    }

    public function campoCsrf(): string
    {
        return '<input type="hidden" name="_csrf" value="' . $this->e($this->csrf->token()) . '">';
    }

    /** aria-current para o item do menu da página atual. */
    public function atual(string $caminho): string
    {
        if ($this->caminhoAtual === $caminho) {
            return ' aria-current="page"';
        }
        if ($caminho !== '/' && str_starts_with($this->caminhoAtual, $caminho . '/')) {
            return ' aria-current="true"';
        }
        return '';
    }

    /** @return list<array{tipo: string, mensagem: string}> */
    public function avisos(): array
    {
        return $this->avisos->consumir();
    }

    public function data(string $data): string
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
        return $d ? $d->format('d/m/Y') : $data;
    }

    public function moeda(float $valor): string
    {
        return 'R$ ' . number_format($valor, 0, ',', '.');
    }

    public function numero(float $valor, int $casas = 1): string
    {
        return number_format($valor, $casas, ',', '.');
    }

    public function periodo(string $periodo): string
    {
        return ['manha' => 'Manhã, das 8h às 12h', 'tarde' => 'Tarde, das 13h às 18h'][$periodo] ?? $periodo;
    }

    public function status(string $status): string
    {
        return [
            'pendente'   => 'Pendente',
            'confirmado' => 'Confirmado',
            'cancelado'  => 'Cancelado',
            'realizado'  => 'Realizado',
        ][$status] ?? $status;
    }
}
