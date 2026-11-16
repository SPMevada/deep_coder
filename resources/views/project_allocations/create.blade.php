@extends('layouts.app')

{{-- @section('content') --}}
<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-9">
            <div class="card border-0 shadow-sm">

                <div class="card-header text-white py-3" style="background: #435d7d;">
                    <h5 class="mb-0">
                        <i class="bi bi-people me-2"></i>Allocate Employee
                    </h5>
                </div>

                <div class="card-body p-4">
                    <form id="allocationForm"
                          action="{{ route('api.projectallocations.store') }}"
                          method="POST">
                        @csrf

                        {{-- Project & Employee --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="project_id" class="form-label fw-semibold">
                                    Project <span class="text-danger">*</span>
                                </label>
                                <select name="project_id" id="project_id" class="form-select select2-field" required>
                                    <option value="">-- Select Project --</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}"
                                                data-start="{{ $project->start_date }}"
                                                data-deadline="{{ $project->deadline }}"
                                                data-start-label="{{ $project->start_date }}"
                                                data-deadline-label="{{ $project->deadline }}"
                                                {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                            {{ $project->code }} - {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text" id="projectHint"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="user_id" class="form-label fw-semibold">
                                    Employee <span class="text-danger">*</span>
                                </label>
                                <select name="user_id" id="user_id" class="form-select select2-field" required>
                                    <option value="">-- Select Employee --</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Role in project --}}
                        <div class="mb-3">
                            <label for="role_in_project" class="form-label fw-semibold">Role in Project</label>
                            <input type="text"
                                   name="role_in_project"
                                   id="role_in_project"
                                   class="form-control"
                                   list="roleSuggestions"
                                   maxlength="50"
                                   placeholder="e.g. Developer, QA, Designer">
                            <datalist id="roleSuggestions">
                                <option value="Developer">
                                <option value="QA Tester">
                                <option value="Designer">
                                <option value="Business Analyst">
                                <option value="Team Lead">
                            </datalist>
                        </div>

                        {{-- Dates --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label fw-semibold">
                                    Start Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="start_date" id="start_date" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label fw-semibold">End Date</label>
                                <input type="date" name="end_date" id="end_date" class="form-control">
                                <div class="form-text">Leave empty to use the project deadline.</div>
                            </div>
                        </div>

                        {{-- Availability message (filled by JS) --}}
                        <div id="availabilityBox" class="alert d-none py-2" role="alert"></div>

                        {{-- Allocation percentage --}}
                        <div class="mb-3">
                            <label for="allocation_percentage" class="form-label fw-semibold">
                                Allocation: <span id="percentValue">50%</span>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="range"
                                   name="allocation_percentage"
                                   id="allocation_percentage"
                                   class="form-range"
                                   min="1" max="100" step="1" value="50">

                            <div class="progress" style="height: 8px;">
                                <div id="percentBar" class="progress-bar" role="progressbar"
                                     style="width: 50%;" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('projectallocations.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </a>

                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle me-1"></i>Allocate Employee
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- @endsection --}}

@push('scripts')
    @include('project_allocations.scrupt', [
        'ignoreId'   => null,
        'submitText' => 'Allocate Employee',
        'busyText'   => 'Allocating...',
        'isCreate'   => true,
    ])
@endpush