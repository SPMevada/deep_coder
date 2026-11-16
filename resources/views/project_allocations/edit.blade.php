@extends('layouts.app')

{{-- @section('content') --}}
<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-9">
            <div class="card border-0 shadow-sm">

                <div class="card-header text-white py-3" style="background: #435d7d;">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Allocation
                    </h5>
                </div>

                <div class="card-body p-4">
                    <form id="allocationForm"
                          action="{{ route('projectallocations.update', $allocation) }}"
                          method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Project & Employee (fixed after creation) --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="project_id" class="form-label fw-semibold">Project</label>
                                <select id="project_id" class="form-select" disabled>
                                    <option value="{{ $allocation->project_id }}"
                                            data-start="{{ $allocation->project->start_date }}"
                                            data-deadline="{{ $allocation->project->deadline }}"
                                            data-start-label="{{ $allocation->project->start_date }}"
                                            data-deadline-label="{{ $allocation->project->deadline }}"
                                            selected>
                                        {{ $allocation->project->code }} - {{ $allocation->project->name }}
                                    </option>
                                </select>
                                <div class="form-text" id="projectHint"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="user_id" class="form-label fw-semibold">Employee</label>
                                <select id="user_id" class="form-select" disabled>
                                    <option value="{{ $allocation->user_id }}" selected>{{ $allocation->user->name }}</option>
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
                                   placeholder="e.g. Developer, QA, Designer"
                                   value="{{ $allocation->role_in_project }}">
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
                                <input type="date" name="start_date" id="start_date" class="form-control" required
                                       value="{{ $allocation->start_date?->format('Y-m-d') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label fw-semibold">End Date</label>
                                <input type="date" name="end_date" id="end_date" class="form-control"
                                       value="{{ $allocation->end_date?->format('Y-m-d') }}">
                                <div class="form-text">Leave empty to use the project deadline.</div>
                            </div>
                        </div>

                        {{-- Availability message (filled by JS) --}}
                        <div id="availabilityBox" class="alert d-none py-2" role="alert"></div>

                        {{-- Allocation percentage --}}
                        <div class="mb-3">
                            <label for="allocation_percentage" class="form-label fw-semibold">
                                Allocation: <span id="percentValue">{{ $allocation->allocation_percentage }}%</span>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="range"
                                   name="allocation_percentage"
                                   id="allocation_percentage"
                                   class="form-range"
                                   min="1" max="100" step="1"
                                   value="{{ $allocation->allocation_percentage }}">

                            <div class="progress" style="height: 8px;">
                                <div id="percentBar" class="progress-bar" role="progressbar"
                                     style="width: {{ $allocation->allocation_percentage }}%;"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('projectallocations.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </a>

                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle me-1"></i>Update Allocation
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
    {{-- @include('project_allocations.scrupt', [
        'ignoreId'   => $allocation->id,
        'submitText' => 'Update Allocation',
        'busyText'   => 'Updating...',
        'isCreate'   => false,
    ]) --}}
@endpush
