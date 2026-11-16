<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectAllocationRequest;
use App\Models\Project;
use App\Models\ProjectAllocation;
use App\Models\User;
use App\Services\AllocationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectAllocationController extends Controller
{
    // Permission + 100% rule: AllocationRequest
    public function store(ProjectAllocationRequest $request)
    {
        $data = $request->validated();
        $project = Project::findOrFail($data['project_id']);

        $data['end_date'] ??= $project->deadline->toDateString();

        try {
            DB::transaction(function () use ($data, $project): void {
                User::whereKey($data['user_id'])->lockForUpdate()->firstOrFail();
                $message = app(AllocationService::class)->validate(
                    $project, User::findOrFail($data['user_id']), Carbon::parse($data['start_date']),
                    Carbon::parse($data['end_date']), (int) $data['allocation_percentage']
                );
                if ($message) {
                    throw ValidationException::withMessages(['allocation_percentage' => $message]);
                }
                ProjectAllocation::create($data);
            });
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Employee allocated successfully'], 201);
    }

    // Only role, percentage and dates can change. Project and employee stay fixed.
    public function update(ProjectAllocationRequest $request, ProjectAllocation $allocation)
    {
        $data = $request->validated();
        $data['end_date'] ??= $allocation->project->deadline->toDateString();

        try {
            DB::transaction(function () use ($allocation, $data): void {
                User::whereKey($allocation->user_id)->lockForUpdate()->firstOrFail();
                $message = app(AllocationService::class)->validate(
                    $allocation->project, $allocation->user, Carbon::parse($data['start_date']),
                    Carbon::parse($data['end_date']), (int) $data['allocation_percentage'], $allocation->id
                );
                if ($message) {
                    throw ValidationException::withMessages(['allocation_percentage' => $message]);
                }
                $allocation->update($data);
            });
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Allocation updated successfully']);
    }

    public function delete($id)
    {
        $allocation = ProjectAllocation::findOrFail($id);

        $this->authorize('delete', $allocation);

        try {
            $allocation->delete();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Allocation removed successfully']);
    }

    /**
     * Used by the create form: shows how much of an employee's time is free.
     * GET /api/employees/{user}/availability?start_date=...&end_date=...&ignore=ID
     */
    public function availability(Request $request, User $user, AllocationService $service)
    {
        $this->authorize('create', ProjectAllocation::class);

        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $used = $service->peak(
            $user->id,
            Carbon::parse($request->start_date)->startOfDay(),
            $request->end_date ? Carbon::parse($request->end_date)->startOfDay() : null,
            $request->integer('ignore') ?: null
        );

        return response()->json([
            'success' => true,
            'used' => $used,
            'available' => max(0, 100 - $used),
        ]);
    }
}
