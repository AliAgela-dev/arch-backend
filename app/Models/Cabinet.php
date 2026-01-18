<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Enums\Status;

class Cabinet extends Model
{
    use HasFactory;
    // Fixed number of drawers each cabinet should have.
    public const DRAWER_COUNT = 4;

    protected $fillable = [
        'id',
        'room_id',
        'name',
        'position_x',
        'position_y',
        'status',
    ];

     protected $casts = [
        'status' => Status::class,
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function drawers()
    {
        return $this->hasMany(Drawer::class, 'cabinet_id', 'id');
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
