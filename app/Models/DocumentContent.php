<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentContent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_document_id',
        'content',
        'page_number',
    ];

    protected $casts = [
        'page_number' => 'integer',
    ];

    public function studentDocument()
    {
        return $this->belongsTo(StudentDocument::class);
    }

    public function embedding()
    {
        return $this->hasOne(DocumentEmbedding::class);
    }
}
