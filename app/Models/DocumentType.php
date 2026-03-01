<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'is_required',
        'requirement_conditions',
        'status',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'requirement_conditions' => 'array',
    ];

    public function studentDocuments()
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
