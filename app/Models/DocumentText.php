<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentText extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_document_id',
        'extracted_text',
        'ocr_status',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function studentDocument(): BelongsTo
    {
        return $this->belongsTo(StudentDocument::class);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'ocr_status' => self::STATUS_PROCESSING,
            'error_message' => null,
        ]);
    }

    public function markAsCompleted(string $text): void
    {
        $this->update([
            'extracted_text' => $text,
            'ocr_status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'ocr_status' => self::STATUS_FAILED,
            'error_message' => $error,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('ocr_status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('ocr_status', self::STATUS_FAILED);
    }
}
