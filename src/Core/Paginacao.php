<?php

declare(strict_types=1);

namespace Toyota\Core;

/** Página pedida (?pagina=2&por_pagina=10) e os metadados devolvidos na resposta. */
final class Paginacao
{
    public const MAXIMO_POR_PAGINA = 50;

    public function __construct(public readonly int $pagina = 1, public readonly int $porPagina = 10)
    {
    }

    public static function daRequisicao(Request $requisicao, int $padrao = 10): self
    {
        $dados = Validador::validar($requisicao->query, [
            'pagina'     => ['opcional', 'inteiro', 'min:1', 'max:100000'],
            'por_pagina' => ['opcional', 'inteiro', 'min:1', 'max:' . self::MAXIMO_POR_PAGINA],
        ]);
        return new self($dados['pagina'] ?? 1, $dados['por_pagina'] ?? $padrao);
    }

    public function offset(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }

    /** @return array{pagina: int, por_pagina: int, total: int, total_paginas: int} */
    public function meta(int $total): array
    {
        return [
            'pagina'        => $this->pagina,
            'por_pagina'    => $this->porPagina,
            'total'         => $total,
            'total_paginas' => max(1, (int) ceil($total / $this->porPagina)),
        ];
    }
}
