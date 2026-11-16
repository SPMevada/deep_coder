@extends('layouts.app')
<div class="container py-4">
    <h2>{{ $task->title }}</h2>
    <p>{{ $task->description }}</p>
    <dl class="row">
        <dt class="col-sm-3">Project</dt><dd class="col-sm-9">{{ $task->project->name }}</dd>
        <dt class="col-sm-3">Assignees</dt><dd class="col-sm-9">{{ $task->assignees->pluck('name')->join(', ') ?: '—' }}</dd>
        <dt class="col-sm-3">Status / Priority</dt><dd class="col-sm-9">{{ ucfirst($task->status) }} / {{ ucfirst($task->priority) }}</dd>
        <dt class="col-sm-3">Due</dt><dd class="col-sm-9">{{ $task->due_date?->format('Y-m-d') ?? '—' }} @if($task->is_overdue)<span class="badge text-bg-danger">Overdue by {{ $task->due_date->diffInDays(today()) }} days</span>@endif</dd>
    </dl>
    @can('changeStatus', $task)
        @if($task->status === 'pending')
            <form method="POST" action="{{ route('tasks.status', $task) }}" class="d-inline">@csrf
                <input type="hidden" name="status" value="in_progress">
                <button class="btn btn-success">Start task</button>
            </form>
        @endif
        <form method="POST" action="{{ route('tasks.status', $task) }}" class="d-inline-flex gap-2">@csrf
            <select name="status" class="form-select" aria-label="Change task status">
                @foreach(App\Models\Task::STATUSES as $status)
                    <option value="{{ $status }}" @selected($task->status === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary">Update status</button>
        </form>
    @endcan
    @can('update', $task)<a class="btn btn-primary" href="{{ route('tasks.edit', $task) }}">Edit</a>@endcan
    <a class="btn btn-secondary" href="{{ route('tasks.index') }}">Back</a>
</div>
