<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class DocumentEmbedding extends Model
{
    use HasFactory, HasUuids, HasNeighbors;

    protected $fillable = [
        'document_content_id',
        'vector',
    ];

    protected $casts = [
        'vector' => Vector::class,
    ];

    public function documentContent()
    {
        return $this->belongsTo(DocumentContent::class);
    }
}
