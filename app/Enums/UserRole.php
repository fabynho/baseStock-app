<?php

namespace App\Enums;

enum UserRole: string
{
    use \App\Enums\Concerns\HasOptions;

    case Admin = 'admin';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::User => 'Usuário',
        };
    }
}
