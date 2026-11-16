{{-- <x-guest-layout title="Sign in"> --}}
@extends('layouts.app')
@push('styles')
    <style>
        .brand-panel {
            background: linear-gradient(135deg, #3730a3 0%, #4f46e5 55%, #7c3aed 100%);
            position: relative;
            overflow: hidden;
        }
        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
        }
        .brand-panel::before { width: 380px; height: 380px; top: -120px; right: -120px; }
        .brand-panel::after  { width: 380px; height: 380px; bottom: -150px; left: -100px; }
        .brand-logo {
            width: 42px; height: 42px; border-radius: 12px;
            background: rgba(255, 255, 255, .2);
            display: inline-flex; align-items: center; justify-content: center;
        }
        .feature-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(255, 255, 255, .15);
            display: inline-flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .login-card { max-width: 420px; }
        .btn-primary { --bs-btn-bg: #4f46e5; --bs-btn-border-color: #4f46e5;
                    --bs-btn-hover-bg: #4338ca; --bs-btn-hover-border-color: #4338ca;
                    --bs-btn-active-bg: #3730a3; --bs-btn-active-border-color: #3730a3; }
        .form-control:focus { border-color: #818cf8; box-shadow: 0 0 0 .2rem rgba(79, 70, 229, .15); }
        .input-group-text { background: #fff; color: #94a3b8; }
    </style>
@endpush


<div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">        
        <div class="col-lg-12 d-flex align-items-center justify-content-center p-4">
            <div class="w-100 login-card">
                <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
                    <span class="brand-logo bg-primary text-white" style="background:#4f46e5 !important">
                        <i class="fa fa-folder-open"></i>
                    </span>
                    <span class="fs-5 fw-bold">{{ config('app.name', 'Laravel') }}</span>
                </div>

                <h2 class="h3 fw-bold mb-4">Project dashboard</h2>
                
                <div class="row g-3 mb-4">
                    @foreach(['projects' => 'Projects', 'active_projects' => 'Active projects', 'completed_projects' => 'Completed projects', 'tasks' => 'Tasks', 'pending_tasks' => 'Pending tasks', 'in_progress_tasks' => 'In progress', 'completed_tasks' => 'Completed tasks', 'overdue_tasks' => 'Overdue tasks'] as $key => $label)
                        <div class="col-6 col-lg-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted">
                                        {{ $label }}
                                    </div>
                                    <div class="fs-3 fw-bold">{{ $stats[$key] }}
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @if($stats['employees'] !== null)
                        <div class="col-6 col-lg-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted">Employees</div>
                                    <div class="fs-3 fw-bold">{{ $stats['employees'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                
                <div class="row g-4">
                    
                    <div class="col-lg-6">
                        <div class="card">
                        <div class="card-header">Recent projects</div>
                    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Project</th><th>Status</th><th>Deadline</th><th>Progress</th></tr></thead><tbody>@forelse($recentProjects as $project)<tr><td><a href="{{ route('projects.show', $project->id) }}">{{ $project->name }}</a></td><td>{{ ucfirst(str_replace('_', ' ', $project->status)) }}</td><td>{{ $project->deadline?->toDateString() }}</td><td>{{ $project->progress_percentage }}%</td></tr>@empty<tr><td colspan="4">No projects yet.</td></tr>@endforelse</tbody></table></div></div></div>
                    
                    <div class="col-lg-6">
                        <div class="card">
                        <div class="card-header">Upcoming deadlines</div><ul class="list-group list-group-flush">@forelse($upcomingTasks as $task)<li class="list-group-item"><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a> — {{ $task->project->name }} <span class="float-end">{{ $task->due_date->toDateString() }}</span></li>@empty<li class="list-group-item">No upcoming task deadlines.</li>@endforelse</ul></div></div>
                    
                    <div class="col-lg-6">
                        <div class="card">
                        <div class="card-header">Upcoming project deadlines</div><ul class="list-group list-group-flush">@forelse($upcomingProjects as $project)<li class="list-group-item"><a href="{{ route('projects.show', $project->id) }}">{{ $project->name }}</a><span class="float-end">{{ $project->deadline->toDateString() }}</span></li>@empty<li class="list-group-item">No upcoming project deadlines.</li>@endforelse</ul></div></div>
                    @if($workloads->isNotEmpty())
                    <div class="col-lg-6">
                        <div class="card">
                        <div class="card-header">Employee workload today</div><ul class="list-group list-group-flush">@foreach($workloads as $workload)<li class="list-group-item">{{ $workload->user->name }}<span class="float-end">{{ $workload->total }}% allocated / {{ max(0, 100 - (int) $workload->total) }}% available</span></li>@endforeach</ul></div></div>@endif
                    
                    <div class="col-12">
                        <div class="card">
                        <div class="card-header text-danger">Overdue tasks</div><ul class="list-group list-group-flush">@forelse($overdueTasks as $task)<li class="list-group-item"><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a> — {{ $task->project->name }} <span class="badge text-bg-danger">{{ $task->due_date->diffInDays(today()) }} days overdue</span></li>@empty<li class="list-group-item">No overdue tasks.</li>@endforelse</ul></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    
</script>
@endpush

{{-- </x-guest-layout> --}}
