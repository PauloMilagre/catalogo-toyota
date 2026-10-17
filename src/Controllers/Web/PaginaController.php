<?php

declare(strict_types=1);

namespace Toyota\Controllers\Web;

use Toyota\Core\Request;
use Toyota\Core\Response;
use Toyota\Core\View;
use Toyota\Services\ModeloService;

/** Páginas públicas: o catálogo e a ficha de cada modelo (antes eram HTML fixo). */
final class PaginaController
{
    public function __construct(private View $view, private ModeloService $modelos)
    {
    }

    public function catalogo(Request $requisicao): Response
    {
        return $this->view->render('catalogo', [
            'titulo'  => 'Catálogo de modelos',
            'modelos' => $this->modelos->ativos(),
        ]);
    }

    /** @param array{slug: string} $parametros */
    public function ficha(Request $requisicao, array $parametros): Response
    {
        $modelo = $this->modelos->porSlug($parametros['slug']);
        return $this->view->render('ficha', [
            'titulo'    => $modelo['nome'],
            'descricao' => 'Ficha técnica do Toyota ' . $modelo['nome'] . ': motorização, potência, câmbio e consumo.',
            'modelo'    => $modelo,
        ]);
    }
}
