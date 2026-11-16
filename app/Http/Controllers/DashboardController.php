<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAllocation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $projects = Project::visibleTo($user);
        $tasks = Task::visibleTo($user);
        $stats = [
            'projects' => (clone $projects)->count(),
            'active_projects' => (clone $projects)->where('status', 'in_progress')->count(),
            'completed_projects' => (clone $projects)->where('status', 'completed')->count(),
            'tasks' => (clone $tasks)->count(),
            'pending_tasks' => (clone $tasks)->where('status', 'pending')->count(),
            'in_progress_tasks' => (clone $tasks)->where('status', 'in_progress')->count(),
            'completed_tasks' => (clone $tasks)->where('status', 'completed')->count(),
            'overdue_tasks' => (clone $tasks)->where('status', '!=', 'completed')->whereNotNull('due_date')->whereDate('due_date', '<', today())->count(),
            'employees' => in_array($user->role?->slug, ['admin', 'manager'], true) ? User::where('role_id', 3)->count() : null,
        ];
        $recentProjects = (clone $projects)->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'completed')])->with('manager:id,name')->latest()->limit(5)->get();
        $upcomingTasks = (clone $tasks)->with('project:id,name')->whereNotNull('due_date')->whereDate('due_date', '>=', today())->orderBy('due_date')->limit(5)->get();
        $overdueTasks = (clone $tasks)->with(['project:id,name', 'assignees:id,name'])->where('status', '!=', 'completed')->whereNotNull('due_date')->whereDate('due_date', '<', today())->orderBy('due_date')->limit(5)->get();
        $upcomingProjects = (clone $projects)->whereNotIn('status', ['completed'])->whereDate('deadline', '>=', today())->orderBy('deadline')->limit(5)->get();
        $workloads = in_array($user->role?->slug, ['admin', 'manager'], true)
            ? ProjectAllocation::with('user:id,name')->where('start_date', '<=', today())->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
                ->whereHas('project', fn ($query) => $query->whereNotIn('status', ['completed', 'cancelled']))
                ->selectRaw('user_id, SUM(allocation_percentage) as total')->groupBy('user_id')->limit(10)->get()
            : collect();

        return view('dashboard', compact('stats', 'recentProjects', 'upcomingTasks', 'upcomingProjects', 'overdueTasks', 'workloads'));
    }

    public function dashboardSummary(Request $request)
    {
        $projects = Project::visibleTo($request->user());
        $tasks = Task::visibleTo($request->user());

        return response()->json(['success' => true, 'data' => [
            'projects' => $projects->count(),
            'tasks' => (clone $tasks)->count(),
            'pending_tasks' => (clone $tasks)->where('status', 'pending')->count(),
            'in_progress_tasks' => (clone $tasks)->where('status', 'in_progress')->count(),
            'completed_tasks' => (clone $tasks)->where('status', 'completed')->count(),
            'overdue_tasks' => (clone $tasks)->where('status', '!=', 'completed')->whereNotNull('due_date')->whereDate('due_date', '<', today())->count(),
        ]]);
    }
}
