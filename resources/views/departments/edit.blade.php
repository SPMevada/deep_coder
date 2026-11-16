
@extends('layouts.app')

<div class="container-xl py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            <div class="card border-0 shadow-sm">
                <div class="card-header text-white py-3"
                    style="background: #435d7d;">
                    <h5 class="mb-0">
                        <i class="bi bi-building me-2"></i>
                        Edit Department
                    </h5>
                </div>

                <div class="card-body p-4">
                    <form id="departmentEdit" action="{{ route('api.departments.update', $department->id) }}" method="PUT">
                        @csrf

                        <div class="mb-3">
                            <label for="name"
                                class="form-label fw-semibold">
                                Department Name
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Enter department name" value="{{ $department->name }}" >
                        </div>

                        {{-- Buttons --}}
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('departments.index') }}"
                            class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>
                                Back
                            </a>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>
                                Update Department
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            $(document).on('submit', '#departmentEdit', function (e) {
                e.preventDefault();
                
                const self = $(this)
                const URL = self.attr('action')

                $.ajax({
                    url: URL,
                    type: 'PUT',
                    data: $(this).serialize(),
                    dataType: 'JSON',
                    success: function (response) {
                        successToast(response.message);
                        window.location.href = "{{ route('departments.index') }}";
                    },
                    error: function (xhr) {
                        $.each(xhr.responseJSON.errors, function (field, messages) {
                            errorToast(messages[0])
                        });
                    }
                });
            });
        });
    </script>
@endpush
