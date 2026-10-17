<?php

declare(strict_types=1);

namespace Toyota\Core;

/** Quem está logado na requisição atual (ou null). Implementado em Services\SessaoUsuario. */
interface UsuarioAtual
{
    /** @return array{id: int, nome: string, email: string, perfil: string}|null */
    public function usuario(): ?array;
}
