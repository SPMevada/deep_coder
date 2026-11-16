@extends('layouts.app')
<div class="container py-4">
    <div class="d-flex justify-content-between mb-3"><h2>Tasks</h2>@can('create', App\Models\Task::class)<a class="btn btn-primary" href="{{ route('tasks.create') }}">Create task</a>@endcan</div>
    <form class="row g-2 mb-3" method="GET">
        <div class="col-md-3">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search tasks">
        </div>

        <div class="col-md-3">
            <select class="form-select" name="project_id">
                <option value="">All projects</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->name }}</option>@endforeach
            </select>
        </div>
        
        <div class="col-md-2">
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                @foreach(App\Models\Task::STATUSES as $status)
                    <option @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach</select>
        </div>
        
        <div class="col-md-2">
            <select class="form-select" name="priority">
                <option value="">All priorities</option>
                @foreach(App\Models\Task::PRIORITIES as $priority)
                    <option @selected(request('priority') === $priority)>{{ $priority }}</option>
                @endforeach
            </select>
        </div>
        
        <div class="col-md-2">
            <button class="btn btn-outline-primary">Filter</button> 
            <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">Clear</a>
        </div>

        <div class="col-md-3">
            <select class="form-select" name="employee_id">
                <option value="">All assignees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
        
        <div class="col-md-3">
            <input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Due from">
        </div>
        
        <div class="col-md-3">
            <input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Due to">
        </div>
        
        <div class="col-12">
            <label>
                <input type="checkbox" name="overdue" value="1" @checked(request()->boolean('overdue'))> Overdue only
            </label> 
            <label class="ms-3">
                <input type="checkbox" name="upcoming" value="1" @checked(request()->boolean('upcoming'))> Upcoming only
            </label>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Project</th>
                    <th>Assignees</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Due</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr class="{{ $task->is_overdue ? 'table-danger' : '' }}">
                        <td>{{ $task->title }}</td>
                        <td>{{ $task->project->name }}</td>
                        <td>{{ $task->assignees->pluck('name')->join(', ') ?: '—' }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($task->status)) }}</td>
                        <td>{{ ucfirst($task->priority) }}</td>
                        <td>{{ $task->due_date?->format('Y-m-d') ?? '—' }} 
                            @if($task->is_overdue)
                                <span class="badge text-bg-danger">Overdue</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('tasks.show', $task) }}">View</a> 
                            @can('changeStatus', $task)
                                @if($task->status === 'pending')
                                    <form method="POST" action="{{ route('tasks.status', $task) }}" class="d-inline">@csrf
                                        <input type="hidden" name="status" value="in_progress">
                                        <button class="btn btn-sm btn-outline-success">Start task</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('tasks.status', $task) }}" class="d-inline-flex gap-1">@csrf
                                    <select name="status" class="form-select form-select-sm" aria-label="Change task status">
                                        @foreach(App\Models\Task::STATUSES as $status)
                                            <option value="{{ $status }}" @selected($task->status === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Update</button>
                                </form>
                            @endcan
                            @can('update', $task)
                                <a href="{{ route('tasks.edit', $task) }}">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No tasks found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $tasks->links() }}
</div>
