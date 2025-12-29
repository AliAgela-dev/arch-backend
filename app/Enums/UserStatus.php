<?php

namespace App\Enums;

enum UserStatus :string
{
    case active = 'active';
    case inactive = 'inactive';
}
