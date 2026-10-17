<?php

declare(strict_types=1);

namespace Toyota\Core;

/** Uma rota registrada: método, padrão de URL, ação e regras de acesso. */
final class Rota
{
    /** @param array{0: class-string, 1: string} $acao */
    public function __construct(
        public readonly string $metodo,
        public readonly string $padrao,
        public readonly string $regex,
        public readonly array $acao,
        public readonly bool $auth,
        public readonly ?string $perfil,
        public readonly bool $csrf
    ) {
    }

    public function exigeLogin(): bool
    {
        return $this->auth || $this->perfil !== null;
    }
}
