<?php

declare(strict_types=1);

namespace Toyota\Services;

use Toyota\Core\HttpException;
use Toyota\Core\Logger;
use Toyota\Core\Paginacao;
use Toyota\Core\Validador;
use Toyota\Repositories\ModeloRepository;

final class ModeloService
{
    public const REGRAS = [
        'slug'           => ['obrigatorio', 'texto', 'min:2', 'max:60', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/'],
        'nome'           => ['obrigatorio', 'texto', 'min:2', 'max:100'],
        'categoria'      => ['obrigatorio', 'texto', 'min:2', 'max:40'],
        'preco'          => ['obrigatorio', 'decimal', 'min:1', 'max:9999999999'],
        'motor'          => ['obrigatorio', 'texto', 'min:2', 'max:100'],
        'potencia_cv'    => ['obrigatorio', 'inteiro', 'min:1', 'max:2000'],
        'cambio'         => ['obrigatorio', 'texto', 'min:2', 'max:60'],
        'consumo_km_l'   => ['obrigatorio', 'decimal', 'min:1', 'max:99'],
        'porta_malas_l'  => ['opcional', 'inteiro', 'min:0', 'max:5000'],
        'carga_kg'       => ['opcional', 'inteiro', 'min:0', 'max:10000'],
        'descricao'      => ['opcional', 'texto', 'max:2000'],
        'imagem'         => ['opcional', 'texto', 'max:100', 'regex:/^[a-z0-9-]+\.(jpg|png|webp)$/'],
        'imagem_alt'     => ['opcional', 'texto', 'max:200'],
        'imagem_largura' => ['opcional', 'inteiro', 'min:1', 'max:4000'],
        'imagem_altura'  => ['opcional', 'inteiro', 'min:1', 'max:4000'],
        'legenda'        => ['opcional', 'texto', 'max:150'],
        'ativo'          => ['opcional', 'booleano'],
    ];

    public function __construct(private ModeloRepository $modelos, private Logger $logger)
    {
    }

    /** @return array{0: list<array<string, mixed>>, 1: array<string, int>} itens e metadados da página */
    public function listar(Paginacao $paginacao, bool $incluirInativos = false): array
    {
        $apenasAtivos = !$incluirInativos;
        $itens = $this->modelos->listar($apenasAtivos, $paginacao->porPagina, $paginacao->offset());
        return [$itens, $paginacao->meta($this->modelos->contar($apenasAtivos))];
    }

    /** @return list<array<string, mixed>> */
    public function ativos(): array
    {
        return $this->modelos->listar(true, Paginacao::MAXIMO_POR_PAGINA, 0);
    }

    public function porSlug(string $slug): array
    {
        $modelo = $this->modelos->buscarPorSlug($slug);
        if ($modelo === null || !$modelo['ativo']) {
            throw HttpException::naoEncontrado('Modelo não encontrado.');
        }
        return $modelo;
    }

    public function porId(int $id, bool $incluirInativos = false): array
    {
        $modelo = $this->modelos->buscarPorId($id);
        if ($modelo === null || (!$modelo['ativo'] && !$incluirInativos)) {
            throw HttpException::naoEncontrado('Modelo não encontrado.');
        }
        return $modelo;
    }

    /** @param array<string, mixed> $entrada */
    public function criar(array $entrada, int $adminId): array
    {
        $dados = $this->validar($entrada, null);
        $id = $this->modelos->criar($dados);
        $this->logger->info('modelo.criado', ['modelo_id' => $id, 'slug' => $dados['slug'], 'por' => $adminId]);
        return $this->porId($id, true);
    }

    /** PUT: substitui o modelo inteiro, então todos os campos obrigatórios precisam vir. */
    public function atualizar(int $id, array $entrada, int $adminId): array
    {
        $this->porId($id, true);
        $dados = $this->validar($entrada, $id);
        $this->modelos->atualizar($id, $dados);
        $this->logger->info('modelo.atualizado', ['modelo_id' => $id, 'por' => $adminId]);
        return $this->porId($id, true);
    }

    public function excluir(int $id, int $adminId): void
    {
        $this->porId($id, true);
        if ($this->modelos->possuiAgendamentos($id)) {
            throw HttpException::conflito(
                'Este modelo tem agendamentos e não pode ser excluído. Desative-o (ativo: false) pelo PUT.'
            );
        }
        $this->modelos->excluir($id);
        $this->logger->info('modelo.excluido', ['modelo_id' => $id, 'por' => $adminId]);
    }

    /** @param array<string, mixed> $entrada */
    private function validar(array $entrada, ?int $idAtual): array
    {
        $dados = Validador::validar($entrada, self::REGRAS);
        $dados['ativo'] ??= true;

        if ($this->modelos->slugEmUso($dados['slug'], $idAtual)) {
            throw new HttpException(409, 'Já existe um modelo com este slug.', ['slug' => 'Slug já usado por outro modelo.']);
        }
        return $dados;
    }
}
