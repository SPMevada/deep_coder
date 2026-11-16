@extends('layouts.app')

<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header text-white py-3 d-flex justify-content-between align-items-center"
                     style="background: #435d7d;">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Employee Details</h5>
                    <span class="badge {{ $employee->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $employee->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="card-body p-4">
                    {{-- Header --}}
                    <div class="d-flex align-items-center mb-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-3"
                             style="width:64px;height:64px;background:#435d7d;font-size:24px;">
                            {{ strtoupper(substr($employee->name, 0, 1)) }}
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $employee->name }}</h5>
                            <div class="text-muted">{{ $employee->designation ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Employee Code</div>
                            <div class="fw-semibold">{{ $employee->employee_code ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Email</div>
                            <div class="fw-semibold">{{ $employee->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Phone</div>
                            <div class="fw-semibold">{{ $employee->phone ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Role</div>
                            <div class="fw-semibold">
                                <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $employee->role->name)) }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Department</div>
                            <div class="fw-semibold">{{ $employee->department->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Joining Date</div>
                            <div class="fw-semibold">
                                {{ $employee->joined_at ? \Carbon\Carbon::parse($employee->joined_at)->format('d M Y') : '-' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Created By</div>
                            <div class="fw-semibold">{{ $employee->createdBy->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Created On</div>
                            <div class="fw-semibold">{{ $employee->created_at->format('d M Y, h:i A') }}</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                        @if (auth()->user()->role === 'admin')
                            <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-primary">
                                <i class="bi bi-pencil-square me-1"></i>Edit
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>