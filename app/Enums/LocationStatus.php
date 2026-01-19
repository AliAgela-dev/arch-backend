<?php

namespace App\Enums;

enum LocationStatus: string
{
    case IN_LOCATION = 'in_location';
    case BORROWED = 'borrowed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
