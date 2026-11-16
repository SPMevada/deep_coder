<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

#[Fillable(['project_id', 'created_by', 'title', 'description', 'start_date', 'due_date', 'priority', 'status'])]
class Task extends Model
{
    protected $appends = ['is_overdue'];

    public const STATUSES = ['pending', 'in_progress', 'completed'];

    public const PRIORITIES = ['low', 'medium', 'high'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'due_date' => 'date'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->role?->slug === 'employee'
            ? $query->whereHas('assignees', fn (Builder $users) => $users->whereKey($user->id))
                ->whereHas('project', fn (Builder $projects) => $projects->visibleTo($user))
            : $query->whereHas('project', fn (Builder $projects) => $projects->visibleTo($user));
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'completed' && $this->due_date !== null && $this->due_date->lt(Carbon::today());
    }
}
