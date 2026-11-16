@extends('layouts.app')

@push('styles')
    {{-- Table styles. If these already live in your layout / shared CSS, delete this block. --}}
    <style>
        .table-responsive { margin: 30px 0; }
        .table-wrapper {
            background: #fff; padding: 20px 25px; border-radius: 3px;
            box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
        }
        .table-title {
            background: #435d7d; color: #fff; padding: 16px 30px;
            margin: -20px -25px 10px; border-radius: 3px 3px 0 0;
        }
        .table-title h2 { margin: 5px 0 0; font-size: 24px; }
        table.table tr th, table.table tr td {
            border-color: #e9e9e9; padding: 12px 15px; vertical-align: middle;
        }
        table.table-striped tbody tr:nth-of-type(odd) { background-color: #fcfcfc; }
        table.table-striped.table-hover tbody tr:hover { background: #f5f5f5; }
        table.table td .action-btn {
            background: none; border: 0; padding: 0; font-weight: bold;
            color: #566787; text-decoration: none; outline: none !important;
        }
        table.table td .action-btn i { font-size: 22px; margin: 0 5px; opacity: .9; }
        table.table td .action-btn.edit   { color: #FFC107; }
        table.table td .action-btn.delete { color: #F44336; }
    </style>
@endpush

@php
    $statusColors = ['planning' => 'secondary', 'active' => 'primary', 'in_progress' => 'primary',
                     'on_hold' => 'warning', 'completed' => 'success', 'cancelled' => 'dark'];
    $isEmployee = auth()->user()->role?->slug === 'employee';
@endphp

<div class="container-xl">
    <div class="table-responsive">
        <div class="table-wrapper">

            {{-- Title bar --}}
            <div class="table-title">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h2>Manage <b>Allocations</b></h2>
                    </div>
                    <div class="col-sm-6 text-end">
                        @can('create', App\Models\ProjectAllocation::class)
                            <a href="{{ route('projectallocations.create') }}" class="btn btn-success">
                                <i class="material-icons align-middle">&#xE147;</i>
                                <span class="align-middle">Add New Allocation</span>
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('projectallocations.index') }}" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select name="project_id" class="form-select">
                        <option value="">All Projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->code }} - {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @unless ($isEmployee)
                    <div class="col-md-4">
                        <select name="user_id" class="form-select">
                            <option value="">All Employees</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" {{ request('user_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endunless

                @unless ($isEmployee)
                    <div class="col-md-3"><select name="department_id" class="form-select"><option value="">All Departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
                @endunless

                <div class="col-md-3 d-flex gap-1">
                    <button class="btn btn-primary">Filter</button>
                    <a href="{{ route('projectallocations.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

            {{-- Table --}}
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Current workload</th>
                        <th>Project</th>
                        <th>Role</th>
                        <th style="width:160px">Allocation</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Project Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($allocations as $allocation)
                        <tr>
                            <td>{{ $allocations->firstItem() + $loop->index }}</td>
                            <td>{{ $allocation->user->name ?? '-' }}</td>
                            <td>{{ $workloads[$allocation->user_id] ?? 0 }}% used / {{ max(0, 100 - (int) ($workloads[$allocation->user_id] ?? 0)) }}% available</td>
                            <td>
                                {{ $allocation->project->name ?? '-' }}
                                <div class="small text-muted">{{ $allocation->project->code ?? '' }}</div>
                            </td>
                            <td>{{ $allocation->role_in_project ?: '-' }}</td>
                            <td>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar {{ $allocation->allocation_percentage >= 100 ? 'bg-danger' : '' }}"
                                         style="width: {{ $allocation->allocation_percentage }}%"></div>
                                </div>
                                <small class="text-muted">{{ $allocation->allocation_percentage }}%</small>
                            </td>
                            <td>{{ $allocation->start_date }}</td>
                            <td>{{ $allocation->end_date ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $statusColors[$allocation->project->status ?? ''] ?? 'secondary' }}">
                                    {{ ucfirst(str_replace('_', ' ', $allocation->project->status ?? '-')) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center">
                                    @can('update', $allocation)
                                        <a href="{{ route('projectallocations.edit', $allocation->id) }}"
                                           class="action-btn edit" title="Edit">
                                            <i class="material-icons">edit</i>
                                        </a>
                                    @endcan

                                    @can('delete', $allocation)
                                        <button type="button"
                                                data-route="{{ route('api.projectallocations.delete', $allocation->id) }}"
                                                class="action-btn delete" title="Delete">
                                            <i class="material-icons">delete</i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No allocations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $allocations->links() }}
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function () {
            $(document).on('click', '.delete', function (e) {
                e.preventDefault();

                if (!confirm('Remove this allocation?')) return;

                $.ajax({
                    url: $(this).data('route'),
                    type: 'POST',   // real method sent as _method, with the CSRF token
                    data: { _method: 'DELETE', _token: "{{ csrf_token() }}" },
                    dataType: 'json',
                    success: function (response) {
                        successToast(response.message);
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            $.each(xhr.responseJSON.errors, function (field, messages) {
                                errorToast(messages[0]);
                            });
                        } else {
                            errorToast(xhr.responseJSON?.message ?? 'Something went wrong.');
                        }
                    }
                });
            });
        });
    </script>
@endpush
