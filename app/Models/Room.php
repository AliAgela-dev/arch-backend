<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Enums\Status;

class Room extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'name',
        'description',
        'canvas_data',
        'status',
    ];

    protected $casts = [
        'canvas_data' => 'array',
        'status' => Status::class,
    ];

    public function cabinets()
    {
        return $this->hasMany(Cabinet::class, 'room_id', 'id');
    }

    protected $keyType = 'string';
    public $incrementing = false;
}
