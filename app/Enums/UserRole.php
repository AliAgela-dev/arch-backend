<?php

namespace App\Enums;

enum UserRole: string
{
    case super_admin = 'super_admin';
    case archivist = 'archivist';
    case faculty_staff = 'faculty_staff';
}
