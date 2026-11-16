<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Project::class);
        $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:planning,in_progress,on_hold,completed'], 'department_id' => ['nullable', 'integer', 'exists:departments,id'], 'employee_id' => ['nullable', 'integer', 'exists:users,id'], 'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from']]);
        $projects = Project::visibleTo($request->user())->with(['department:id,name', 'manager:id,name'])
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed')])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($search) => $search->where('name', 'like', '%'.$request->query('search').'%')->orWhere('code', 'like', '%'.$request->query('search').'%')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('employee_id'), fn ($query) => $query->whereHas('employees', fn ($users) => $users->whereKey($request->integer('employee_id'))))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('deadline', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('deadline', '<=', $request->query('date_to')))
            ->paginate(15)->withQueryString();

        return response()->json(['success' => true, 'data' => $projects]);
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        return response()->json(['success' => true, 'data' => $project->load(['department', 'manager', 'employees:id,name', 'tasks.assignees:id,name'])]);
    }

    public function store(ProjectRequest $request)
    {
        $this->authorize('create', Project::class);

        $data = $request->validated();
        $data['code'] = $this->generateCode();
        $data['created_by'] = Auth::id();
        $data['progress'] = 0;
        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;

        try {
            $project = Project::create($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully',
            'data' => ['id' => $project->id, 'code' => $project->code],
        ], 201);
    }

    public function update(ProjectRequest $request, $id)
    {
        $project = Project::findOrFail($id);

        $this->authorize('update', $project);

        $data = $request->validated();
        $data['completed_at'] = $data['status'] === 'completed'
            ? ($project->completed_at ?? now())
            : null;

        try {
            $project->update($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'data' => ['id' => $project->id, 'code' => $project->code],
        ]);
    }

    public function delete($id)
    {
        $project = Project::findOrFail($id);
        $this->authorize('delete', $project);

        try {
            Project::where('id', $id)->delete();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully',
        ]);
    }

    private function generateCode(): string
    {
        $next = (Project::withTrashed()->max('id') ?? 0) + 1;

        return 'PRJ-'.str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
