<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasUuids, HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    public $table = 'users';
    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'last_login' => 'datetime',
        ];
    }

    public function assignRoleWithHierarchy(string $roleName): void
    {
        $rolesToSync = match ($roleName) {
            UserRole::super_admin->value => [
                UserRole::super_admin->value,
                UserRole::archivist->value,
                UserRole::faculty_staff->value
            ],
            UserRole::archivist->value => [
                UserRole::archivist->value,
                UserRole::faculty_staff->value
            ],
            default => [$roleName],
        };
        $this->syncRoles($rolesToSync);
    }
}
