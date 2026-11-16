<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Notifications\TaskAssigned;
use App\Services\TaskStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Task::class);
        request()->validate([
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
        $tasks = Task::visibleTo(request()->user())->with(['project:id,name,code', 'assignees:id,name'])
            ->when(request()->filled('search'), fn ($query) => $query->where('title', 'like', '%'.request()->query('search').'%'))
            ->when(request()->filled('project_id'), fn ($query) => $query->where('project_id', request()->integer('project_id')))
            ->when(request()->filled('status'), fn ($query) => $query->where('status', request()->query('status')))
            ->when(request()->filled('priority'), fn ($query) => $query->where('priority', request()->query('priority')))
            ->when(request()->filled('employee_id'), fn ($query) => $query->whereHas('assignees', fn ($users) => $users->whereKey(request()->integer('employee_id'))))
            ->when(request()->filled('date_from'), fn ($query) => $query->whereDate('due_date', '>=', request()->query('date_from')))
            ->when(request()->filled('date_to'), fn ($query) => $query->whereDate('due_date', '<=', request()->query('date_to')))
            ->when(request()->boolean('upcoming'), fn ($query) => $query->whereNotNull('due_date')->whereDate('due_date', '>=', today())->where('status', '!=', 'completed'))
            ->when(request()->boolean('overdue'), fn ($query) => $query->whereNotNull('due_date')->whereDate('due_date', '<', today())->where('status', '!=', 'completed'))
            ->paginate(15)->withQueryString();

        return response()->json(['success' => true, 'data' => $tasks]);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json(['success' => true, 'data' => $task->load(['project', 'assignees:id,name', 'creator:id,name'])]);
    }

    public function store(TaskRequest $request): JsonResponse
    {
        $data = $request->validated();
        $assignees = $data['assignees'] ?? [];
        unset($data['assignees']);
        $task = DB::transaction(function () use ($data, $assignees, $request): Task {
            $task = Task::create([...$data, 'created_by' => $request->user()->id]);
            $task->assignees()->sync($assignees);

            return $task->load('assignees');
        });
        foreach ($task->assignees as $user) {
            $user->notify(new TaskAssigned($task));
        }

        return response()->json(['success' => true, 'message' => 'Task created successfully.', 'data' => $task], 201);
    }

    public function update(TaskRequest $request, Task $task): JsonResponse
    {
        $data = $request->validated();
        unset($data['status']);
        $assignees = $data['assignees'] ?? [];
        unset($data['assignees']);
        DB::transaction(function () use ($task, $data, $assignees): void {
            $task->update($data);
            $task->assignees()->sync($assignees);
        });

        return response()->json(['success' => true, 'message' => 'Task updated successfully.', 'data' => $task->fresh(['project', 'assignees'])]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        $task->delete();

        return response()->json(['success' => true, 'message' => 'Task deleted successfully.']);
    }

    public function changeStatus(Request $request, Task $task, TaskStatusService $taskStatusService): JsonResponse
    {
        $this->authorize('changeStatus', $task);
        $data = $request->validate(['status' => ['required', 'in:pending,in_progress,completed']]);
        $task = $taskStatusService->change($task, $request->user(), $data['status']);

        return response()->json(['success' => true, 'message' => 'Task status updated.', 'data' => $task]);
    }
}
