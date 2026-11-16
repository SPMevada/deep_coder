<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Admin    : full access to every project
 * Manager  : view ALL projects, create, but update/delete ONLY their own
 * Employee : view only, and only projects they are allocated to
 */
class ProjectPolicy
{
    // Runs before every method below. Admin passes everything except restore/forceDelete.
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($ability, ['restore', 'forceDelete'])) {
            return null; // fall through to the methods below (both false)
        }

        return $user->role?->slug === 'admin' ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role?->slug, ['manager', 'employee']);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->role?->slug === 'manager'
            || ($user->role?->slug === 'employee' && $project->employees()->whereKey($user->id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->role?->slug === 'manager';
    }

    public function update(User $user, Project $project): bool
    {
        return $this->ownsProject($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->ownsProject($user, $project);
    }

    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }

    // "Own" = the manager created the project.
    // To also include the assigned project manager, use:
    //   $project->created_by === $user->id || $project->manager_id === $user->id
    private function ownsProject(User $user, Project $project): bool
    {
        return ($user->role?->slug === 'manager' || $user->role?->slug === 'admin') && $project->created_by === $user->id;
    }
}
