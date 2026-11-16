<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Policies\EmployeePolicy;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'is_active', 'role_id', 'department_id', 'designation', 'phone', 'joined_at', 'created_by'])]
#[Hidden(['password', 'remember_token'])]
#[UsePolicy(EmployeePolicy::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

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
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function managerRoles()
    {
        return $this->hasMany(Role::class, 'id')->where('slug', 'manager');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function scopeEmployees()
    {
        return $this->hasMany(self::class)->where('role_id', 3);
    }

    public function allocations()
    {
        return $this->hasMany(ProjectAllocation::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class)->withTimestamps();
    }
}
