<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TempUpload extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    protected $fillable = [
        'original_name',
        'mime_type',
        'size',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'size' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('temp')
            ->singleFile();
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>=', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
