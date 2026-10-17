<?php

declare(strict_types=1);

namespace Toyota\Core;

use RuntimeException;

/**
 * Registro de componentes da aplicação (injeção de dependências simples).
 * Cada componente é criado uma vez, na primeira vez que alguém pede,
 * e reaproveitado depois. As receitas ficam em config/servicos.php.
 */
final class Container
{
    /** @var array<string, callable(self): mixed> */
    private array $fabricas = [];

    /** @var array<string, mixed> */
    private array $instancias = [];

    /** @param callable(self): mixed $fabrica */
    public function definir(string $id, callable $fabrica): void
    {
        $this->fabricas[$id] = $fabrica;
    }

    public function instancia(string $id, mixed $objeto): void
    {
        $this->instancias[$id] = $objeto;
    }

    /**
     * @template T of object
     * @param class-string<T>|string $id
     * @return ($id is class-string<T> ? T : mixed)
     */
    public function get(string $id): mixed
    {
        if (!array_key_exists($id, $this->instancias)) {
            $fabrica = $this->fabricas[$id] ?? throw new RuntimeException("Componente não registrado: {$id}");
            $this->instancias[$id] = $fabrica($this);
        }
        return $this->instancias[$id];
    }
}
