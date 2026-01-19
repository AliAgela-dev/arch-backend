<?php

namespace App\Enums;

enum BorrowingStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case BORROWED = 'borrowed';
    case RETURNED = 'returned';
    case OVERDUE = 'overdue';
}
