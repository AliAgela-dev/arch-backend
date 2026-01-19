<?php

namespace App\Models;

use App\Enums\BorrowingStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Borrowing extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'student_document_id',
        'status',
        'requested_at',
        'approved_at',
        'rejected_at',
        'borrowed_at',
        'due_date',
        'returned_at',
        'notes',
        'admin_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'status' => BorrowingStatus::class,
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'borrowed_at' => 'datetime',
        'due_date' => 'datetime',
        'returned_at' => 'datetime',
    ];

    /**
     * Get the user who requested this borrowing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the student document being borrowed.
     */
    public function studentDocument(): BelongsTo
    {
        return $this->belongsTo(StudentDocument::class);
    }

    /**
     * Scope: Get pending borrowings.
     */
    public function scopePending($query)
    {
        return $query->where('status', BorrowingStatus::PENDING);
    }

    /**
     * Scope: Get approved borrowings.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', BorrowingStatus::APPROVED);
    }

    /**
     * Scope: Get borrowed (active) borrowings.
     */
    public function scopeBorrowed($query)
    {
        return $query->where('status', BorrowingStatus::BORROWED);
    }

    /**
     * Scope: Get overdue borrowings.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', BorrowingStatus::OVERDUE)
            ->orWhere(function ($q) {
                $q->whereIn('status', [BorrowingStatus::BORROWED, BorrowingStatus::APPROVED])
                    ->where('due_date', '<', now());
            });
    }

    /**
     * Check if the borrowing is overdue.
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        if ($this->status === BorrowingStatus::OVERDUE) {
            return true;
        }

        if (in_array($this->status, [BorrowingStatus::BORROWED, BorrowingStatus::APPROVED])) {
            return $this->due_date->isPast();
        }

        return false;
    }

    /**
     * Calculate days until due date.
     * Returns negative if overdue.
     */
    public function daysUntilDue(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return (int) now()->diffInDays($this->due_date, false);
    }

    /**
     * Calculate days overdue.
     * Returns 0 if not overdue.
     */
    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return abs((int) now()->diffInDays($this->due_date, false));
    }

    /**
     * Boot method to set default requested_at timestamp.
     */
    protected static function booted()
    {
        static::creating(function ($borrowing) {
            if (!$borrowing->requested_at) {
                $borrowing->requested_at = now();
            }
            if (!$borrowing->status) {
                $borrowing->status = BorrowingStatus::PENDING;
            }
        });
    }
}
