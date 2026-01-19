<?php

namespace App\Models;

use App\Enums\FileStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class StudentDocument extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    protected $fillable = [
        'student_id',
        'document_type_id',
        'file_number',
        'file_status',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'file_status' => FileStatus::class,
        'submitted_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')
            ->singleFile();
    }

    public function scopeComplete($query)
    {
        return $query->where('file_status', FileStatus::COMPLETE);
    }

    public function scopeIncomplete($query)
    {
        return $query->where('file_status', FileStatus::INCOMPLETE);
    }

    public function scopeDraft($query)
    {
        return $query->where('file_status', FileStatus::DRAFT);
    }

    /**
     * All borrowing records for this document.
     * Part of Borrowing System module - isolated feature.
     */
    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }

    /**
     * Get the current active borrowing (if any).
     */
    public function currentBorrowing()
    {
        return $this->hasOne(Borrowing::class)
            ->whereIn('status', ['approved', 'borrowed'])
            ->latest();
    }

    /**
     * Check if document is currently borrowed.
     */
    public function isBorrowed(): bool
    {
        return $this->currentBorrowing()->exists();
    }
}
