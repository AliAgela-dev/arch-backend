<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends Model
{ use SoftDeletes;
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'status',
    ];
     

    protected $dates = ['deleted_at'];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class , 'user_faculties');
    } 
}
