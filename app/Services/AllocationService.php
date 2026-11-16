<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectAllocation;
use App\Models\User;
use Carbon\Carbon;

class AllocationService
{
    /**
     * Highest total allocation % the employee has on any day of the given period.
     */
    public function peak(int $userId, Carbon $start, ?Carbon $end, ?int $ignoreId = null): int
    {
        $rows = ProjectAllocation::where('user_id', $userId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereHas('project', fn ($p) => $p->whereNotIn('status', ['completed', 'cancelled']))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $start))
            ->when($end, fn ($q) => $q->where('start_date', '<=', $end))
            ->get(['allocation_percentage', 'start_date', 'end_date']);

        // Days to test: the new start date + every start date that falls inside the period
        $days = $rows->pluck('start_date')
            ->filter(fn ($d) => $d->gt($start) && (! $end || $d->lte($end)))
            ->push($start)
            ->unique(fn ($d) => $d->toDateString());

        return (int) $days->map(fn ($day) => $rows
            ->filter(fn ($r) => $r->start_date->lte($day) && (! $r->end_date || $r->end_date->gte($day)))
            ->sum('allocation_percentage')
        )->max();
    }

    /**
     * Returns an error message, or null when the allocation is allowed.
     */
    public function validate(
        Project $project, User $employee, Carbon $start, ?Carbon $end, int $percentage, ?int $ignoreId = null
    ): ?string {
        $end ??= $project->deadline;

        if (in_array($project->status, ['completed', 'cancelled'])) {
            return 'Completed or cancelled projects cannot accept allocations.';
        }

        if ($start->lt($project->start_date) || $end->gt($project->deadline)) {
            return 'Allocation dates must be between the project start date ('
                .$project->start_date->format('d M Y').') and deadline ('
                .$project->deadline->format('d M Y').').';
        }

        if ($end->lt($start)) {
            return 'End date cannot be before the start date.';
        }

        $duplicate = ProjectAllocation::where('project_id', $project->id)
            ->where('user_id', $employee->id)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('start_date', '<=', $end)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $start))
            ->exists();

        if ($duplicate) {
            return "{$employee->name} is already allocated to this project in these dates.";
        }

        $used = $this->peak($employee->id, $start, $end, $ignoreId);

        if ($used + $percentage > 100) {
            $available = max(0, 100 - $used);

            return "{$employee->name} is already allocated {$used}% in this period. "
                 ."Only {$available}% is available.";
        }

        return null;
    }
}
