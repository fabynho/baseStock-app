<?php

namespace App\Enums\Concerns;

trait HasOptions
{
    /** @return array<int, array{value: string, label: string}> para selects/filtros no Vue */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
