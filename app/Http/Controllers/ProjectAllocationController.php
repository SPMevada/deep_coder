<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectAllocationRequest;
use App\Models\Department;
use App\Models\Project;
use App\Models\ProjectAllocation;
use App\Models\User;
use App\Services\AllocationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectAllocationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ProjectAllocation::class);

        $allocations = ProjectAllocation::visibleTo(Auth::user())   // employee: own only
            ->with(['project:id,department_id,code,name,status', 'user:id,name'])
            ->when($request->project_id, fn ($q, $v) => $q->where('project_id', $v))
            ->when($request->user_id, fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->department_id, fn ($q, $v) => $q->whereHas('project', fn ($project) => $project->where('department_id', $v)))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $projects = Project::visibleTo(Auth::user())->orderBy('name')->get(['id', 'code', 'name']);
        $employees = $this->assignableUsers();
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $workloads = ProjectAllocation::query()->where('start_date', '<=', today())->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
            ->whereHas('project', fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
            ->selectRaw('user_id, SUM(allocation_percentage) as total')->groupBy('user_id')->pluck('total', 'user_id');

        return view('project_allocations.index', compact('allocations', 'projects', 'employees', 'departments', 'workloads'));
    }

    public function create()
    {
        $this->authorize('create', ProjectAllocation::class);

        $projects = $this->manageableProjects();
        $employees = $this->assignableUsers();

        return view('project_allocations.create', compact('projects', 'employees'));
    }

    public function edit($id)
    {
        $allocation = ProjectAllocation::findOrFail($id);

        $this->authorize('update', $allocation);

        $allocation->load('project:id,code,name,start_date,deadline', 'user:id,name');

        return view('project_allocations.edit', compact('allocation'));
    }

    public function store(ProjectAllocationRequest $request, AllocationService $service): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $service): void {
            User::whereKey($data['user_id'])->lockForUpdate()->firstOrFail();
            $project = Project::findOrFail($data['project_id']);
            $data['end_date'] ??= $project->deadline->toDateString();
            $employee = User::findOrFail($data['user_id']);
            $error = $service->validate($project, $employee, Carbon::parse($data['start_date']), $data['end_date'] ? Carbon::parse($data['end_date']) : null, (int) $data['allocation_percentage']);
            if ($error) {
                throw ValidationException::withMessages(['allocation_percentage' => $error]);
            }
            ProjectAllocation::create($data);
        });

        return redirect()->route('projectallocations.index')->with('success', 'Employee allocated successfully.');
    }

    public function update(ProjectAllocationRequest $request, ProjectAllocation $allocation, AllocationService $service): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($allocation, $data, $service): void {
            User::whereKey($allocation->user_id)->lockForUpdate()->firstOrFail();
            $data['end_date'] ??= $allocation->project->deadline->toDateString();
            $error = $service->validate($allocation->project, $allocation->user, Carbon::parse($data['start_date']), $data['end_date'] ? Carbon::parse($data['end_date']) : null, (int) $data['allocation_percentage'], $allocation->id);
            if ($error) {
                throw ValidationException::withMessages(['allocation_percentage' => $error]);
            }
            $allocation->update($data);
        });

        return redirect()->route('projectallocations.index')->with('success', 'Allocation updated successfully.');
    }

    public function destroy(ProjectAllocation $allocation): RedirectResponse
    {
        $this->authorize('delete', $allocation);
        $allocation->delete();

        return redirect()->route('projectallocations.index')->with('success', 'Allocation removed successfully.');
    }

    // Projects the logged-in user may allocate people to
    private function manageableProjects()
    {
        $user = Auth::user();

        return Project::whereNotIn('status', ['completed', 'cancelled'])
            ->when($user->role->slug === 'manager', fn ($q) => $q->where('created_by', $user->id))
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'start_date', 'deadline']);
    }

    // Active employees and managers (never admins)
    private function assignableUsers()
    {
        return User::where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('slug', ['employee', 'manager']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
