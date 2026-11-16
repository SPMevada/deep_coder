<?php

namespace App\Models;

use App\Policies\ProjectPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'code', 'description', 'department_id', 'manager_id', 'created_by', 'start_date', 'deadline', 'status', 'priority', 'progress', 'completed_at'])]
#[UsePolicy(ProjectPolicy::class)]
class Project extends Model
{
    use SoftDeletes;

    const STATUSES = ['planning', 'in_progress', 'on_hold', 'completed'];

    const PRIORITIES = ['low', 'medium', 'high'];

    protected $appends = ['progress_percentage'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'deadline' => 'date', 'completed_at' => 'datetime'];
    }

    public function getIsDelayedAttribute(): bool
    {
        return $this->status !== 'completed' && $this->deadline !== null && $this->deadline->lt(now()->startOfDay());
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'project_allocations')
            ->withPivot('allocation_percentage', 'role_in_project', 'start_date', 'end_date')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function getProgressPercentageAttribute(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        $completed = $this->completed_tasks_count ?? $this->tasks()->where('status', 'completed')->count();

        return $total === 0 ? 0 : (int) round(($completed / $total) * 100);
    }

    public function scopeVisibleTo($q, User $user)
    {
        if (in_array($user->role?->slug, ['admin', 'manager'])) {
            return $q;
        }

        return $q->whereHas('employees', fn ($e) => $e->where('users.id', $user->id));
    }
}
