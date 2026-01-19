<?php

namespace App\Enums;

enum StudentStatus: string
{
    case ACTIVE = 'active';
    case GRADUATED = 'graduated';
    case TRANSFERRED = 'transferred';
    case WITHDRAWN = 'withdrawn';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
