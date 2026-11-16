<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Project;
use App\Models\ProjectAllocation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeManager($request);
        $this->validateFilters($request);
        $projects = $this->projectQuery($request)->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed')])->with(['department:id,name', 'manager:id,name'])->paginate(20)->withQueryString();
        $tasks = $this->taskQuery($request)->with(['project:id,name', 'assignees:id,name'])->latest()->paginate(20, ['*'], 'tasks_page')->withQueryString();
        $taskGroups = $this->taskQuery($request)->selectRaw('status, priority, COUNT(*) as total')->groupBy('status', 'priority')->get();
        $workloads = $this->allocationQuery($request)->with('user:id,name')->get();
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $employees = User::whereHas('role', fn ($query) => $query->where('slug', 'employee'))->orderBy('name')->get(['id', 'name']);

        return view('reports.index', compact('projects', 'tasks', 'taskGroups', 'workloads', 'departments', 'employees'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeManager($request);
        $this->validateFilters($request);
        $type = $request->query('type', 'projects');
        abort_unless(in_array($type, ['projects', 'tasks', 'allocations'], true), 422);
        $query = match ($type) {
            'tasks' => $this->taskQuery($request)->with('project'),
            'allocations' => $this->allocationQuery($request)->with('user:id,name'),
            default => $this->projectQuery($request)->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed')]),
        };

        return response()->streamDownload(function () use ($query, $type): void {
            $stream = fopen('php://output', 'w');
            if ($type === 'tasks') {
                fputcsv($stream, ['Task', 'Project', 'Status', 'Priority', 'Start date', 'Due date', 'Overdue']);
                $query->chunk(500, function ($tasks) use ($stream): void {
                    foreach ($tasks as $task) {
                        fputcsv($stream, array_map([$this, 'safeCsv'], [$task->title, $task->project->name, $task->status, $task->priority, $task->start_date, $task->due_date, $task->is_overdue ? 'Yes' : 'No']));
                    }
                });
            } elseif ($type === 'allocations') {
                fputcsv($stream, ['Employee', 'Current allocation %', 'Availability %']);
                $query->chunk(500, function ($rows) use ($stream): void {
                    foreach ($rows as $row) {
                        fputcsv($stream, array_map([$this, 'safeCsv'], [$row->user->name, $row->total, max(0, 100 - (int) $row->total)]));
                    }
                });
            } else {
                fputcsv($stream, ['Project', 'Code', 'Status', 'Priority', 'Progress %', 'Deadline']);
                $query->chunk(500, function ($projects) use ($stream): void {
                    foreach ($projects as $project) {
                        fputcsv($stream, array_map([$this, 'safeCsv'], [$project->name, $project->code, $project->status, $project->priority, $project->progress_percentage, $project->deadline]));
                    }
                });
            }
            fclose($stream);
        }, $type.'-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless(in_array($request->user()->role?->slug, ['admin', 'manager'], true), 403);
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'status' => ['nullable', 'in:planning,in_progress,on_hold,completed,pending'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'overdue' => ['nullable', 'boolean'],
        ]);
    }

    private function projectQuery(Request $request): Builder
    {
        return Project::query()
            ->when($request->filled('status') && in_array($request->query('status'), Project::STATUSES, true), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('employee_id'), fn ($query) => $query->whereHas('employees', fn ($employees) => $employees->whereKey($request->integer('employee_id'))))
            ->when($request->filled('project_id'), fn ($query) => $query->whereKey($request->integer('project_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('deadline', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('deadline', '<=', $request->query('date_to')));
    }

    private function taskQuery(Request $request): Builder
    {
        return Task::query()
            ->when($request->filled('status') && in_array($request->query('status'), Task::STATUSES, true), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->query('priority')))
            ->when($request->filled('department_id'), fn ($query) => $query->whereHas('project', fn ($project) => $project->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('employee_id'), fn ($query) => $query->whereHas('assignees', fn ($users) => $users->whereKey($request->integer('employee_id'))))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('due_date', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('due_date', '<=', $request->query('date_to')))
            ->when($request->boolean('overdue'), fn ($query) => $query->whereNotNull('due_date')->whereDate('due_date', '<', today())->where('status', '!=', 'completed'));
    }

    private function allocationQuery(Request $request): Builder
    {
        $periodStart = $request->query('date_from', today()->toDateString());
        $periodEnd = $request->query('date_to', today()->toDateString());

        return ProjectAllocation::query()->selectRaw('user_id, SUM(allocation_percentage) as total')
            ->when($request->filled('employee_id'), fn ($query) => $query->where('user_id', $request->integer('employee_id')))
            ->when($request->filled('department_id'), fn ($query) => $query->whereHas('project', fn ($project) => $project->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->whereDate('start_date', '<=', $periodEnd)
            ->where(fn ($dates) => $dates->whereNull('end_date')->orWhereDate('end_date', '>=', $periodStart))
            ->whereHas('project', fn ($query) => $query->whereNotIn('status', ['completed', 'cancelled']))
            ->groupBy('user_id')->orderBy('user_id');
    }

    private function safeCsv(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[\s]*[=+@-]/u', $value) ? "'".Str::replace("'", "''", $value) : $value;
    }
}
