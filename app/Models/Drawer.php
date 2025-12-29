<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Enums\Status;

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

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'status' => Status::class,
    ];

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id', 'id');
    }

    protected static function booted()
    {
        // Our IDs are UUID strings, so we generate them at creation time.
        static::creating(function ($model) {
            $model->id = $model->id ?? (string) Str::uuid();
        });
    }

    public function usagePercent(int $current_count = 0): int
    {
        //currentCount is 0 for now until files are implemented.
        $capacity = (int) ($this->capacity ?? 0);
        if ($capacity <= 0) return 0;

        return (int) round(($current_count / $capacity) * 100);
    }

    public function capacityColor(int $current_count = 0): string
    {
        $percent = $this->usagePercent($current_count);

        if ($percent >= 95) return 'red';
        if ($percent >= 80) return 'yellow';
        return 'green';
    }

    public function capacityStatus(int $current_count = 0): string
    {
        $percent = $this->usagePercent($current_count);

        if ($percent >= 95) return 'critical';
        if ($percent >= 80) return 'warning';
        return 'normal';
    }
}