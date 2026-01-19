<?php

namespace App\Models;

use App\Enums\LocationStatus;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_number',
        'name',
        'nationality',
        'email',
        'phone',
        'faculty_id',
        'program_id',
        'drawer_id',
        'enrollment_year',
        'graduation_year',
        'student_status',
        'location_status',
    ];

    protected $casts = [
        'student_status' => StudentStatus::class,
        'location_status' => LocationStatus::class,
        'enrollment_year' => 'integer',
        'graduation_year' => 'integer',
        'faculty_id' => 'integer',
        'program_id' => 'integer',
    ];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function drawer()
    {
        return $this->belongsTo(Drawer::class);
    }

    public function documents()
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function scopeActive($query)
    {
        return $query->where('student_status', StudentStatus::ACTIVE);
    }

    public function scopeInLocation($query)
    {
        return $query->where('location_status', LocationStatus::IN_LOCATION);
    }

    public function scopeBorrowed($query)
    {
        return $query->where('location_status', LocationStatus::BORROWED);
    }
}
