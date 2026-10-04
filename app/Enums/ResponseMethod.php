<?php

namespace App\Enums;

enum ResponseMethod: string
{
    case Online = 'online';
    case Email = 'email';
    case Mail = 'mail';
    case InPerson = 'in_person';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Email => 'Email',
            self::Mail => 'Mail',
            self::InPerson => 'In person',
        };
    }
}
