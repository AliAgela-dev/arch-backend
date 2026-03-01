<?php

namespace App\Enums\Pipeline;

enum RefinementStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
