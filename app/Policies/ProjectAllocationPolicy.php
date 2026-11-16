<?php

namespace App\Policies;

use App\Models\ProjectAllocation;
use App\Models\User;

class ProjectAllocationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->role?->slug === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role?->slug, ['manager', 'employee']);
    }

    public function view(User $user, ProjectAllocation $allocation): bool
    {
        return match ($user->role?->slug) {
            'manager' => true,
            'employee' => $allocation->user_id === $user->id,
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->role?->slug === 'manager';
    }

    // A manager manages allocations only on projects they are allowed to update
    public function update(User $user, ProjectAllocation $allocation): bool
    {
        return $user->can('update', $allocation->project);
    }

    public function delete(User $user, ProjectAllocation $allocation): bool
    {
        return $user->can('update', $allocation->project);
    }
}
