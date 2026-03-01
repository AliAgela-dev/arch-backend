<?php

namespace App\Models;

use App\Casts\RefinementDataCast;
use App\Enums\Pipeline\RefinementStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRefinement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_document_id',
        'structured_data',
        'confidence_score',
        'raw_response',
        'refinement_status',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'structured_data' => RefinementDataCast::class,
        'confidence_score' => 'decimal:2',
        'processed_at' => 'datetime',
        'refinement_status' => RefinementStatus::class,
    ];

    public function studentDocument()
    {
        return $this->belongsTo(StudentDocument::class);
    }
}
