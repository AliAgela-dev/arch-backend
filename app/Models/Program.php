<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'faculty_id',
        'code',
        'name_ar',
        'name_en',
        'status',
    ];
 

    protected $dates = ['deleted_at'];
    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }
}
