<?php

namespace App\Enums;

enum FileStatus: string
{
    case COMPLETE = 'complete';
    case INCOMPLETE = 'incomplete';
    case DRAFT = 'draft';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
