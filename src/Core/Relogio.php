<?php

declare(strict_types=1);

namespace Toyota\Core;

use DateTimeImmutable;

/** Data de "hoje". Os testes passam uma data fixa para não depender do calendário. */
final class Relogio
{
    public function __construct(private ?DateTimeImmutable $fixo = null)
    {
    }

    public function hoje(): DateTimeImmutable
    {
        return ($this->fixo ?? new DateTimeImmutable())->setTime(0, 0);
    }
}
