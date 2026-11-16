<?php

namespace App\Models;

use App\Policies\ProjectAllocationPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['project_id', 'user_id', 'allocation_percentage', 'role_in_project', 'start_date', 'end_date'])]
#[UsePolicy(ProjectAllocationPolicy::class)]
class ProjectAllocation extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleTo($q, User $user)
    {
        return $user->role?->slug === 'employee' ? $q->where('user_id', $user->id) : $q;
    }
}
