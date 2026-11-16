@extends('layouts.app')
<div class="container py-4">
    <h2>{{ $task->exists ? 'Edit task' : 'Create task' }}</h2>
    <form method="POST" action="{{ $task->exists ? route('tasks.update', $task) : route('tasks.store') }}">
        @csrf 
        
        @if($task->exists) 
            @method('PUT') 
        @endif
        <div class="mb-3">
            <label class="form-label">Project</label>
            <select class="form-select" name="project_id" required>
                @foreach($projects as $project)
                <option value="{{ $project->id }}" @selected(old('project_id', $task->project_id ?? request('project_id')) == $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
            @error('project_id')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
        
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" value="{{ old('title', $task->title) }}" required>
            @error('title')
                <div class="text-danger">
                    {{ $message }}
                </div>
            @enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description">{{ old('description', $task->description) }}</textarea></div>
        <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Start date</label><input class="form-control" type="date" name="start_date" value="{{ old('start_date', $task->start_date?->toDateString()) }}">@error('start_date')<div class="text-danger">{{ $message }}</div>@enderror</div><div class="col-md-6 mb-3"><label class="form-label">Due date</label><input class="form-control" type="date" name="due_date" value="{{ old('due_date', $task->due_date?->toDateString()) }}">@error('due_date')<div class="text-danger">{{ $message }}</div>@enderror</div></div>
        <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Priority</label><select class="form-select" name="priority">@foreach(App\Models\Task::PRIORITIES as $priority)<option value="{{ $priority }}" @selected(old('priority', $task->priority ?? 'medium') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
            @unless($task->exists)
                <div class="col-md-6 mb-3"><label class="form-label">Status</label><select class="form-select" name="status">@foreach(App\Models\Task::STATUSES as $status)<option value="{{ $status }}" @selected(old('status', 'pending') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select></div>
            @endunless
        </div>
        <div class="mb-3"><label class="form-label">Assignees</label><select class="form-select assignees" name="assignees[]" multiple>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(in_array($employee->id, old('assignees', $task->assignees->pluck('id')->all())))>{{ $employee->name }}</option>@endforeach</select><small>Assignees must already be allocated to this project.</small>@error('assignees')<div class="text-danger">{{ $message }}</div>@enderror</div>
        <button class="btn btn-primary">Save task</button> <a class="btn btn-secondary" href="{{ route('tasks.index') }}">Cancel</a>
    </form>
</div>

@push('scripts')
    <script>
        $('.assignees').select2();
    </script>
@endpush
