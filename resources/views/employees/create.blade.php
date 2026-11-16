@extends('layouts.app')

{{-- @section('content') --}}
<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header text-white py-3" style="background: #435d7d;">
                    <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Create Employee</h5>
                </div>

                <div class="card-body p-4">
                    <form id="employeeCreate" action="{{ route('api.employees.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            {{-- <div class="col-md-6 mb-3">
                                <label for="employee_code" class="form-label fw-semibold">Employee Code <span class="text-danger">*</span></label>
                                <input type="text" name="employee_code" id="employee_code"
                                       class="form-control" placeholder="e.g. EMP001" maxlength="30">
                            </div> --}}

                            <div class="col-md-12 mb-3">
                                <label for="name" class="form-label fw-semibold">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name" class="form-control"
                                       placeholder="Enter full name">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-semibold">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" name="email" id="email" class="form-control"
                                       placeholder="name@company.com">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="phone" class="form-control"
                                       placeholder="Enter phone number" maxlength="20">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-semibold">
                                    Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" name="password" id="password" class="form-control"
                                       placeholder="Minimum 8 characters">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label fw-semibold">
                                    Confirm Password <span class="text-danger">*</span>
                                </label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                       class="form-control" placeholder="Re-enter password">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label fw-semibold">
                                    Role <span class="text-danger">*</span>
                                </label>
                                <select name="role_id" id="role_id" class="form-select role_id">
                                    <option value="">{{ "Select Role" }}</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="department_id" class=" form-label fw-semibold">Department <span class="text-danger">*</span></label>
                                <select name="department_id" id="department_id" class="department_id form-select">
                                    <option value="">-- Select Department --</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="designation" class="form-label fw-semibold">Designation <span class="text-danger">*</span></label>
                                <input type="text" name="designation" id="designation" class="form-control"
                                       placeholder="e.g. Software Developer">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="joined_at" class="form-label fw-semibold">Joining Date <span class="text-danger">*</span></label>
                                <input type="date" name="joined_at" id="joined_at" class="form-control">
                            </div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check-circle me-1"></i>Create Employee
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
    $('.department_id, .role_id').select2();

    $(document).on('submit', '#employeeCreate', function (e) {
        e.preventDefault();

        const self = $(this);
        const $btn  = $('#submitBtn').prop('disabled', true);

        $.ajax({
            url: self.attr('action'),
            type: 'POST',
            data: self.serialize(),
            dataType: 'JSOn',
            success: function (response) {
                successToast(response.message);
                window.location.href = "{{ route('employees.index') }}";
            },
            error: function (xhr) {
                $btn.prop('disabled', false);

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