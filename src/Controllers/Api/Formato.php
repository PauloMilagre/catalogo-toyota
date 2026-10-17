<?php

declare(strict_types=1);

namespace Toyota\Controllers\Api;

/** Formato padronizado dos objetos devolvidos pela API. */
final class Formato
{
    public static function usuario(array $u): array
    {
        return [
            'id'     => $u['id'],
            'nome'   => $u['nome'],
            'email'  => $u['email'],
            'perfil' => $u['perfil'],
        ];
    }

    public static function modelo(array $m): array
    {
        return [
            'id'            => $m['id'],
            'slug'          => $m['slug'],
            'nome'          => $m['nome'],
            'categoria'     => $m['categoria'],
            'preco'         => $m['preco'],
            'motor'         => $m['motor'],
            'potencia_cv'   => $m['potencia_cv'],
            'cambio'        => $m['cambio'],
            'consumo_km_l'  => $m['consumo_km_l'],
            'porta_malas_l' => $m['porta_malas_l'],
            'carga_kg'      => $m['carga_kg'],
            'descricao'     => $m['descricao'],
            'imagem'        => $m['imagem'],
            'imagem_alt'    => $m['imagem_alt'],
            'ativo'         => $m['ativo'],
        ];
    }

    public static function agendamento(array $a): array
    {
        return [
            'id'          => $a['id'],
            'data'        => $a['data'],
            'periodo'     => $a['periodo'],
            'status'      => $a['status'],
            'telefone'    => $a['telefone'],
            'observacoes' => $a['observacoes'],
            'modelo'      => ['id' => $a['modelo_id'], 'slug' => $a['modelo_slug'], 'nome' => $a['modelo_nome']],
            'usuario'     => ['id' => $a['usuario_id'], 'nome' => $a['usuario_nome']],
            'criado_em'   => $a['criado_em'],
        ];
    }
}
