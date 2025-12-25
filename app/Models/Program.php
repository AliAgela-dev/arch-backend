<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = [
        'faculty_id',
        'code',
        'name_ar',
        'name_en',
        'status',
    ];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }
}
