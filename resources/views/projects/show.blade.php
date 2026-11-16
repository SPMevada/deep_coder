@extends('layouts.app')

@php
    $statusColors   = ['planning' => 'secondary', 'active' => 'primary', 'in_progress' => 'primary',
                       'on_hold' => 'warning', 'completed' => 'success', 'cancelled' => 'dark'];
    $priorityColors = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];

    $daysLeft = now()->startOfDay()->diffInDays($project->deadline, false); // negative = overdue
@endphp

{{-- @section('content') --}}
<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-9">
            <div class="card border-0 shadow-sm">

                <div class="card-header text-white py-3 d-flex justify-content-between align-items-center"
                     style="background: #435d7d;">
                    <h5 class="mb-0">
                        <i class="bi bi-kanban me-2"></i>Project Details
                    </h5>
                    <span class="badge bg-{{ $statusColors[$project->status] ?? 'secondary' }}">
                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                    </span>
                </div>

                <div class="card-body p-4">

                    <div class="mb-3">
                        <div class="text-muted small">{{ $project->code }}</div>
                        <h4 class="mb-1">
                            {{ $project->name }}
                            @if ($project->is_delayed)
                                <span class="badge bg-danger ms-2">Delayed</span>
                            @endif
                        </h4>
                        <p class="text-muted mb-0">{{ $project->description ?: 'No description provided.' }}</p>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Progress</span>
                            <span>{{ $project->progress_percentage }}%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar {{ $project->is_delayed ? 'bg-danger' : '' }}"
                                 role="progressbar"
                                 style="width: {{ $project->progress_percentage }}%;"
                                 aria-valuenow="{{ $project->progress_percentage }}"
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Department</div>
                            <div class="fw-semibold">{{ $project->department->name ?? '-' }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Project Manager</div>
                            <div class="fw-semibold">{{ $project->manager->name ?? '-' }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Start Date</div>
                            <div class="fw-semibold">{{ $project->start_date }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Deadline</div>
                            <div class="fw-semibold {{ $project->is_delayed ? 'text-danger' : '' }}">
                                {{ $project->deadline }}
                                @if (! in_array($project->status, ['completed', 'cancelled']))
                                    <small>
                                        @if ($daysLeft < 0)
                                            ({{ abs($daysLeft) }} {{ Str::plural('day', abs($daysLeft)) }} overdue)
                                        @elseif ($daysLeft === 0)
                                            (due today)
                                        @else
                                            ({{ $daysLeft }} {{ Str::plural('day', $daysLeft) }} left)
                                        @endif
                                    </small>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Priority</div>
                            <span class="badge bg-{{ $priorityColors[$project->priority] ?? 'secondary' }}">
                                {{ ucfirst($project->priority) }}
                            </span>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Completed On</div>
                            <div class="fw-semibold">{{ $project->completed_at?->format('d M Y') ?? '-' }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Created By</div>
                            <div class="fw-semibold">{{ $project->createdBy->name ?? '-' }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Created On</div>
                            <div class="fw-semibold">{{ $project->created_at->format('d M Y, h:i A') }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted small">Last Updated</div>
                            <div class="fw-semibold">{{ $project->updated_at->format('d M Y, h:i A') }}</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-xl pb-4"><div class="card"><div class="card-header d-flex justify-content-between"><span>Project tasks</span>@can('update', $project)<a href="{{ route('tasks.create', ['project_id' => $project->id]) }}">Add task</a>@endcan</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Task</th><th>Assignees</th><th>Status</th><th>Due date</th><th></th></tr></thead><tbody>@forelse($project->tasks as $task)<tr class="{{ $task->is_overdue ? 'table-danger' : '' }}"><td>{{ $task->title }}</td><td>{{ $task->assignees->pluck('name')->join(', ') }}</td><td>{{ $task->status }}</td><td>{{ $task->due_date?->toDateString() ?? '—' }}</td><td><a href="{{ route('tasks.show', $task) }}">View</a></td></tr>@empty<tr><td colspan="5">No tasks.</td></tr>@endforelse</tbody></table></div></div></div>
{{-- @endsection --}}

@push('scripts')
<script>
    $(function () {
        
    });
</script>
@endpush
