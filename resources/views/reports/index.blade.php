@extends('layouts.app')
<div class="container py-4">
    <h2>Reports</h2>
    <form class="row g-2 mb-3" method="GET" action="{{ route('reports.index') }}">
        <div class="col-md-2"><select name="status" class="form-select"><option value="">All status</option>@foreach(['planning','in_progress','on_hold','completed','pending'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="priority" class="form-select"><option value="">All priority</option>@foreach(['low','medium','high'] as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ $priority }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="department_id" class="form-select"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="employee_id" class="form-select"><option value="">All employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}"></div><div class="col-md-2"><input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}"></div>
        <div class="col-12"><label><input type="checkbox" name="overdue" value="1" @checked(request()->boolean('overdue'))> Overdue tasks</label> <button class="btn btn-primary btn-sm">Apply</button> <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a></div>
    </form>
    <div class="mb-4"><a class="btn btn-outline-primary" href="{{ route('reports.export', array_merge(request()->query(), ['type' => 'projects'])) }}">Export projects CSV</a> <a class="btn btn-outline-primary" href="{{ route('reports.export', array_merge(request()->query(), ['type' => 'tasks'])) }}">Export tasks CSV</a> <a class="btn btn-outline-primary" href="{{ route('reports.export', array_merge(request()->query(), ['type' => 'allocations'])) }}">Export workload CSV</a></div>
    <h3>Project progress</h3>
    <table class="table"><thead><tr><th>Project</th><th>Status</th><th>Progress</th><th>Department</th></tr></thead><tbody>@forelse($projects as $project)<tr><td>{{ $project->name }}</td><td>{{ $project->status }}</td><td>{{ $project->progress_percentage }}%</td><td>{{ $project->department?->name }}</td></tr>@empty<tr><td colspan="4">No projects match these filters.</td></tr>@endforelse</tbody></table>{{ $projects->links() }}
    <h3>Task counts by status and priority</h3>
    <table class="table"><thead><tr><th>Status</th><th>Priority</th><th>Count</th></tr></thead><tbody>@forelse($taskGroups as $group)<tr><td>{{ $group->status }}</td><td>{{ $group->priority }}</td><td>{{ $group->total }}</td></tr>@empty<tr><td colspan="3">No tasks match these filters.</td></tr>@endforelse</tbody></table>
    <h3>Employee workload</h3>
    <table class="table"><thead><tr><th>Employee</th><th>Allocation</th><th>Available</th></tr></thead><tbody>@forelse($workloads as $workload)<tr><td>{{ $workload->user->name }}</td><td>{{ $workload->total }}%</td><td>{{ max(0, 100 - (int) $workload->total) }}%</td></tr>@empty<tr><td colspan="3">No allocations match these filters.</td></tr>@endforelse</tbody></table>
    <h3>Tasks</h3>
    <table class="table"><thead><tr><th>Task</th><th>Project</th><th>Status</th><th>Priority</th><th>Due</th></tr></thead><tbody>@forelse($tasks as $task)<tr class="{{ $task->is_overdue ? 'table-danger' : '' }}"><td>{{ $task->title }}</td><td>{{ $task->project->name }}</td><td>{{ $task->status }}</td><td>{{ $task->priority }}</td><td>{{ $task->due_date?->toDateString() ?? '—' }}</td></tr>@empty<tr><td colspan="5">No tasks match these filters.</td></tr>@endforelse</tbody></table>{{ $tasks->links('pagination::bootstrap-5') }}
</div>
