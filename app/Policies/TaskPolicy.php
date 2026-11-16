<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($ability === 'changeStatus') {
            return null;
        }

        return $user->role?->slug === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role?->slug, ['manager', 'employee'], true);
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->role?->slug === 'manager') {
            return $user->can('view', $task->project);
        }

        return $user->role?->slug === 'employee'
            && $task->assignees()->whereKey($user->id)->exists()
            && $user->can('view', $task->project);
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->slug, ['admin', 'manager'], true);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->create($user) && $user->can('update', $task->project);
    }

    public function changeStatus(User $user, Task $task): bool
    {
        return $task->assignees()->whereKey($user->id)->exists()
            && $user->can('view', $task->project);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }
}
