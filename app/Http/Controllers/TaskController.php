<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Services\TaskStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Task::class);
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'status' => ['nullable', 'in:pending,in_progress,completed'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'overdue' => ['nullable', 'boolean'],
            'upcoming' => ['nullable', 'boolean'],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $query = Task::visibleTo($request->user())->with(['project:id,name,code', 'assignees:id,name']);
        $query->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->string('priority')))
            ->when($request->filled('employee_id'), fn ($q) => $q->whereHas('assignees', fn ($users) => $users->whereKey($request->integer('employee_id'))))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('due_date', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('due_date', '<=', $request->query('date_to')))
            ->when($request->boolean('upcoming'), fn ($q) => $q->whereNotNull('due_date')->whereDate('due_date', '>=', today())->where('status', '!=', 'completed'))
            ->when($request->boolean('overdue'), fn ($q) => $q->whereNotNull('due_date')->where('due_date', '<', today())->where('status', '!=', 'completed'));

        $tasks = $query->latest()->paginate(15)->withQueryString();

        return view('tasks.index', [
            'tasks' => $tasks,
            'projects' => Project::visibleTo($request->user())->orderBy('name')->get(['id', 'name']),
            'employees' => $request->user()->role?->slug === 'employee'
                ? User::whereKey($request->user()->id)->get(['id', 'name'])
                : User::whereHas('role', fn ($query) => $query->where('slug', 'employee'))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Task::class);
        $projects = Project::visibleTo($request->user())->where('status', '!=', 'completed')
            ->when($request->user()->role?->slug === 'manager', fn ($query) => $query->where('created_by', $request->user()->id))
            ->orderBy('name')->get();
        $employees = User::where('is_active', true)->whereHas('role', fn ($q) => $q->where('slug', 'employee'))->orderBy('name')->get(['id', 'name']);

        return view('tasks.form', ['task' => new Task, 'projects' => $projects, 'employees' => $employees]);
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $assignees = $data['assignees'] ?? [];
        unset($data['assignees']);

        $task = DB::transaction(function () use ($data, $assignees, $request): Task {
            $task = Task::create([...$data, 'created_by' => $request->user()->id]);
            $task->assignees()->sync($assignees);

            return $task->load('assignees');
        });
        $task->assignees->each(fn (User $employee) => $employee->notify(new TaskAssigned($task)));

        return redirect()->route('tasks.show', $task)->with('success', 'Task created successfully.');
    }

    public function show(Request $request, Task $task): View
    {
        $this->authorize('view', $task);

        return view('tasks.show', ['task' => $task->load(['project', 'assignees', 'creator'])]);
    }

    public function edit(Request $request, Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks.form', [
            'task' => $task->load('assignees'),
            'projects' => Project::visibleTo($request->user())->where(fn ($query) => $query->where('status', '!=', 'completed')->orWhereKey($task->project_id))
                ->when($request->user()->role?->slug === 'manager', fn ($query) => $query->where('created_by', $request->user()->id))
                ->orderBy('name')->get(),
            'employees' => User::where('is_active', true)->whereHas('role', fn ($q) => $q->where('slug', 'employee'))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $data = $request->validated();
        unset($data['status']);
        $assignees = $data['assignees'] ?? [];
        unset($data['assignees']);
        $oldAssignees = $task->assignees()->pluck('users.id')->all();

        DB::transaction(function () use ($task, $data, $assignees): void {
            $task->update($data);
            $task->assignees()->sync($assignees);
        });
        $task->load('assignees')->assignees->whereNotIn('id', $oldAssignees)->each(fn (User $employee) => $employee->notify(new TaskAssigned($task)));

        return redirect()->route('tasks.show', $task)->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }

    public function changeStatus(Request $request, Task $task, TaskStatusService $taskStatusService): RedirectResponse
    {
        $this->authorize('changeStatus', $task);
        $data = $request->validate(['status' => ['required', 'in:pending,in_progress,completed']]);
        $taskStatusService->change($task, $request->user(), $data['status']);

        return redirect()->route('tasks.show', $task)->with('success', 'Task status updated.');
    }
}
