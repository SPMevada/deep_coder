<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Project::class);
        $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:planning,in_progress,on_hold,completed'], 'department_id' => ['nullable', 'integer', 'exists:departments,id'], 'employee_id' => ['nullable', 'integer', 'exists:users,id'], 'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from']]);
        $projects = Project::visibleTo($request->user())->with(['department:id,name', 'manager:id,name', 'createdBy:id,name'])
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed')])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->query('search').'%')->orWhere('code', 'like', '%'.$request->query('search').'%')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('employee_id'), fn ($query) => $query->whereHas('employees', fn ($users) => $users->whereKey($request->integer('employee_id'))))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('deadline', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('deadline', '<=', $request->query('date_to')))
            ->paginate(15)->withQueryString();

        $departments = Department::orderBy('name')->get(['id', 'name']);
        $employees = $request->user()->role?->slug === 'employee'
            ? User::whereKey($request->user()->id)->get(['id', 'name'])
            : User::whereHas('role', fn ($query) => $query->where('slug', 'employee'))->orderBy('name')->get(['id', 'name']);

        return view('projects.index', compact('projects', 'departments', 'employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Project::class);

        $departments = Department::get();
        $managers = User::whereHas('role', fn ($query) => $query->where('slug', 'manager'))->get();

        return view('projects.create', compact('departments', 'managers'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $project = Project::withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed')])
            ->with(['tasks.assignees:id,name'])->findOrFail($id);

        $this->authorize('view', $project);

        $departments = Department::get();
        $managers = User::whereHas('role', fn ($query) => $query->where('slug', 'manager'))->get();

        return view('projects.show', compact('project', 'departments', 'managers'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $project = Project::findOrFail($id);

        $this->authorize('update', $project);

        $departments = Department::get();
        $managers = User::whereHas('role', fn ($query) => $query->where('slug', 'manager'))->get();

        return view('projects.edit', compact('project', 'departments', 'managers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function store(ProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);
        $data = $request->validated();
        $data['code'] = $this->generateCode();
        $data['created_by'] = Auth::id();
        $data['progress'] = 0;
        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;
        Project::create($data);

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $data = $request->validated();
        $data['completed_at'] = $data['status'] === 'completed' ? ($project->completed_at ?? now()) : null;
        $project->update($data);

        return redirect()->route('projects.show', $project->id)->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }

    private function generateCode(): string
    {
        return 'PRJ-'.str_pad((string) ((Project::withTrashed()->max('id') ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }
}
