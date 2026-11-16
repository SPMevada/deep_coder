@extends('layouts.app')

{{-- @section('content') --}}
<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-9">
            <div class="card border-0 shadow-sm">

                <div class="card-header text-white py-3" style="background: #435d7d;">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Project
                    </h5>
                </div>

                <div class="card-body p-4">
                    <form id="projectEdit"
                          action="{{ route('api.projects.update', $project->id) }}"
                          method="PUT">
                        @csrf
                        @method('PUT')

                        {{-- Project Name & Code --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label fw-semibold">
                                    Project Name <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       name="name"
                                       id="name"
                                       class="form-control"
                                       placeholder="Enter project name"
                                       value="{{ $project->name }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Project Code</label>
                                <input type="text" class="form-control" value="{{ $project->code }}" disabled>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea name="description"
                                      id="description"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Enter project description">{{ $project->description }}</textarea>
                        </div>

                        {{-- Department & Manager --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label fw-semibold">
                                    Department <span class="text-danger">*</span>
                                </label>
                                <select name="department_id" id="department_id" class="form-select department_id">
                                    <option value="">-- Select Department --</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}"
                                            {{ $project->department_id == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="manager_id" class="form-label fw-semibold">
                                    Project Manager <span class="text-danger">*</span>
                                </label>
                                <select name="manager_id" id="manager_id" class="form-select manager_id">
                                    <option value="">-- Select Manager --</option>
                                    @foreach ($managers as $manager)
                                        <option value="{{ $manager->id }}"
                                            {{ $project->manager_id == $manager->id ? 'selected' : '' }}>
                                            {{ $manager->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Dates --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label fw-semibold">
                                    Start Date <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       name="start_date"
                                       id="start_date"
                                       class="form-control"
                                       value="{{ $project->start_date->format('Y-m-d') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="deadline" class="form-label fw-semibold">
                                    Deadline <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       name="deadline"
                                       id="deadline"
                                       class="form-control"
                                       value="{{ $project->deadline->format('Y-m-d') }}">
                            </div>
                        </div>

                        {{-- Status & Priority --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label fw-semibold">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select name="status" id="status" class="form-select">
                                    @foreach (\App\Models\Project::STATUSES as $status)
                                        <option value="{{ $status }}" {{ $project->status == $status ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label fw-semibold">
                                    Priority <span class="text-danger">*</span>
                                </label>
                                <select name="priority" id="priority" class="form-select">
                                    @foreach (\App\Models\Project::PRIORITIES as $priority)
                                        <option value="{{ $priority }}" {{ $project->priority == $priority ? 'selected' : '' }}>
                                            {{ ucfirst($priority) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Progress (read-only: calculated from tasks) --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Progress: {{ $project->progress }}%
                                <small class="text-muted">(calculated automatically from tasks)</small>
                            </label>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ $project->progress }}%;"
                                     aria-valuenow="{{ $project->progress }}"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </a>

                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle me-1"></i>Update Project
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
<script>
$(function () {
    $('.department_id, .manager_id').select2({ width: '100%' });
    $('#deadline').attr('min', $('#start_date').val());

    $('#start_date').on('change', function () {
        $('#deadline').attr('min', $(this).val());

        if ($('#deadline').val() && $('#deadline').val() < $(this).val()) {
            $('#deadline').val('');
        }
    });

    $('#projectEdit').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const btn  = $('#submitBtn');

        btn.prop('disabled', true)
           .html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

        $.ajax({
            url: form.attr('action'),
            type: 'PUT',
            data: form.serialize(),
            dataType: 'json',

            success: function (response) {
                if (response.success) {
                    successToast(response.message);
                    window.location.href = "{{ route('projects.index') }}";
                } else {
                    errorToast(response.message || 'Unable to update project.');
                    resetButton();
                }
            },

            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        errorToast(messages[0]);
                    });
                } else {
                    errorToast(xhr.responseJSON?.message || 'Something went wrong.');
                }

                resetButton();
            }
        });

        function resetButton() {
            btn.prop('disabled', false)
               .html('<i class="bi bi-check-circle me-1"></i> Update Project');
        }
    });
});
</script>
@endpush