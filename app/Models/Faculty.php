<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'status',
    ];
      use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
