<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Cabinet;
use Illuminate\Support\Str;

class Drawer extends Model
{
    protected $fillable = [
        'id',
        'cabinet_id',
        'number',
        'label',
        'capacity',
        'status',
    ];

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id', 'id');
    }

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->id = $model->id ?? (string) Str::uuid();
        });
    }

    
}
