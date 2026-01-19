<?php

namespace App\Services;

use App\Enums\BorrowingStatus;
use App\Models\Borrowing;
use Illuminate\Support\Facades\DB;

class BorrowingService
{
    /**
     * Approve a borrowing request.
     *
     * @param Borrowing $borrowing
     * @param array $data ['due_days' => int, 'admin_notes' => string|null]
     * @return Borrowing
     */
    public function approveBorrowing(Borrowing $borrowing, array $data): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $data) {
            // Calculate due date
            $dueDays = $data['due_days'] ?? config('borrowing.default_duration_days', 14);
            
            $borrowing->update([
                'status' => BorrowingStatus::APPROVED,
                'approved_at' => now(),
                'due_date' => now()->addDays($dueDays),
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);

            return $borrowing->fresh();
        });
    }

    /**
     * Reject a borrowing request.
     *
     * @param Borrowing $borrowing
     * @param array $data ['rejection_reason' => string, 'admin_notes' => string|null]
     * @return Borrowing
     */
    public function rejectBorrowing(Borrowing $borrowing, array $data): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $data) {
            $borrowing->update([
                'status' => BorrowingStatus::REJECTED,
                'rejected_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);

            return $borrowing->fresh();
        });
    }
}
