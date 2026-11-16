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
        .input-group-text { background: #fff; color: #94a3b8;}

		body {
			color: #566787;
			background: #f5f5f5;
			font-family: 'Varela Round', sans-serif;
			font-size: 13px;
		}

		.table-responsive {
			margin: 30px 0;
		}

		.table-wrapper {
			background: #fff;
			padding: 20px 25px;
			border-radius: 3px;
			min-width: 1000px;
			box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
		}

		.table-title {
			padding-bottom: 15px;
			background: #435d7d;
			color: #fff;
			padding: 16px 30px;
			min-width: 100%;
			margin: -20px -25px 10px;
			border-radius: 3px 3px 0 0;
		}

		.table-title h2 {
			margin: 5px 0 0;
			font-size: 24px;
		}

		.table-title .btn-group {
			float: right;
		}

		.table-title .btn {
			color: #fff;
			float: right;
			font-size: 13px;
			border: none;
			min-width: 50px;
			border-radius: 2px;
			border: none;
			outline: none !important;
			margin-left: 10px;
		}

		.table-title .btn i {
			float: left;
			font-size: 21px;
			margin-right: 5px;
		}

		.table-title .btn span {
			float: left;
			margin-top: 2px;
		}

		table.table tr th,
		table.table tr td {
			border-color: #e9e9e9;
			padding: 12px 15px;
			vertical-align: middle;
		}

		table.table tr th:first-child {
			width: 60px;
		}

		table.table tr th:last-child {
			width: 100px;
		}

		table.table-striped tbody tr:nth-of-type(odd) {
			background-color: #fcfcfc;
		}

		table.table-striped.table-hover tbody tr:hover {
			background: #f5f5f5;
		}

		table.table th i {
			font-size: 13px;
			margin: 0 5px;
			cursor: pointer;
		}

		table.table td:last-child i {
			opacity: 0.9;
			font-size: 22px;
			margin: 0 5px;
		}

		table.table td a,
		table.table td button {
			font-weight: bold;
			color: #566787;
			display: inline-block;
			text-decoration: none;
			outline: none !important;
		}

		table.table td a:hover 
		table.table td button:hover 
        {
			color: #2196F3;
		}

		table.table td a.edit {
			color: #FFC107;
		}

		table.table td button.delete {
			color: #F44336;
		}

		table.table td i {
			font-size: 19px;
		}

		table.table .avatar {
			border-radius: 50%;
			vertical-align: middle;
			margin-right: 10px;
		}

		.pagination {
			float: right;
			margin: 0 0 5px;
		}

		.pagination li a {
			border: none;
			font-size: 13px;
			min-width: 30px;
			min-height: 30px;
			color: #999;
			margin: 0 2px;
			line-height: 30px;
			border-radius: 2px !important;
			text-align: center;
			padding: 0 6px;
		}

		.pagination li a:hover {
			color: #666;
		}

		.pagination li.active a,
		.pagination li.active a.page-link {
			background: #03A9F4;
		}

		.pagination li.active a:hover {
			background: #0397d6;
		}

		.pagination li.disabled i {
			color: #ccc;
		}

		.pagination li i {
			font-size: 16px;
			padding-top: 6px
		}

		.hint-text {
			float: left;
			margin-top: 10px;
			font-size: 13px;
		}

		/* Custom checkbox */
		.custom-checkbox {
			position: relative;
		}

		.custom-checkbox input[type="checkbox"] {
			opacity: 0;
			position: absolute;
			margin: 5px 0 0 3px;
			z-index: 9;
		}

		.custom-checkbox label:before {
			width: 18px;
			height: 18px;
		}

		.custom-checkbox label:before {
			content: '';
			margin-right: 10px;
			display: inline-block;
			vertical-align: text-top;
			background: white;
			border: 1px solid #bbb;
			border-radius: 2px;
			box-sizing: border-box;
			z-index: 2;
		}

		.custom-checkbox input[type="checkbox"]:checked+label:after {
			content: '';
			position: absolute;
			left: 6px;
			top: 3px;
			width: 6px;
			height: 11px;
			border: solid #000;
			border-width: 0 3px 3px 0;
			transform: inherit;
			z-index: 3;
			transform: rotateZ(45deg);
		}

		.custom-checkbox input[type="checkbox"]:checked+label:before {
			border-color: #03A9F4;
			background: #03A9F4;
		}

		.custom-checkbox input[type="checkbox"]:checked+label:after {
			border-color: #fff;
		}

		.custom-checkbox input[type="checkbox"]:disabled+label:before {
			color: #b8b8b8;
			cursor: auto;
			box-shadow: none;
			background: #ddd;
		}

		/* Modal styles */
		.modal .modal-dialog {
			max-width: 400px;
		}

		.modal .modal-header,
		.modal .modal-body,
		.modal .modal-footer {
			padding: 20px 30px;
		}

		.modal .modal-content {
			border-radius: 3px;
			font-size: 14px;
		}

		.modal .modal-footer {
			background: #ecf0f1;
			border-radius: 0 0 3px 3px;
		}

		.modal .modal-title {
			display: inline-block;
		}

		.modal .form-control {
			border-radius: 2px;
			box-shadow: none;
			border-color: #dddddd;
		}

		.modal textarea.form-control {
			resize: vertical;
		}

		.modal .btn {
			border-radius: 2px;
			min-width: 100px;
		}

		.modal form label {
			font-weight: normal;
		}
    </style>
@endpush
<div class="container-xl">
    <div class="table-responsive">
        <div class="table-wrapper">
            <div class="table-title">
                <div class="row">
                    <div class="col-sm-6">
                        <h2>Manage <b>Projects</b></h2>
                    </div>
                    @can('create', App\Models\Project::class)
                        <div class="col-sm-6">
                            <a href="{{ route('projects.create') }}" class="btn btn-success" data-toggle="modal"><i class="material-icons">&#xE147;</i> <span>Add New Project</span></a>					
                        </div>
                    @endcan
                </div>
            </div>
            <form method="GET" action="{{ route('projects.index') }}" class="row g-2 mb-3">
                <div class="col-md-4"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search name or code"></div>
                <div class="col-md-3"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(App\Models\Project::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
                <div class="col-md-3"><select class="form-select" name="department_id"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-outline-primary">Filter</button> <a class="btn btn-outline-secondary" href="{{ route('projects.index') }}">Clear</a></div>
                <div class="col-md-3"><select class="form-select" name="employee_id"><option value="">All assigned employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Deadline from"></div>
                <div class="col-md-3"><input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Deadline to"></div>
            </form>
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Department</th>
                        <th>Manager</th>
                        <th>Created By</th>
                        <th>Start Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Progress</th>
                        <th>Completed AT</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($projects as $key => $project)
                        <tr>
                            <td>{{ $key+1 }}</td>
                            <td>{{ $project->name }}</td>
                            <td>{{ $project->code }}</td>
                            <td>{{ $project->description }}</td>
                            <td>{{ $project->department->name }}</td>
                            <td>{{ $project->manager->name }}</td>
                            <td>{{ $project->createdBy->name }}</td>
                            <td>{{ $project->start_date }}</td>
                            <td>{{ $project->deadline ?? '-' }}</td>
                            <td>{{ ucfirst($project->status) }}</td>
                            <td>{{ ucfirst($project->priority) }}</td>
                            <td>{{ $project->progress_percentage }}%</td>
                            <td>{{ $project->completed_at ?? '-' }}</td>
							<td class="text-center">
								<div class="d-inline-flex align-items-center gap-2">
									@can('update', $project)
                                    	<a href="{{ route('projects.edit', $project->id) }}" class="action-btn edit" title="Edit"><i class="material-icons">edit</i></a>
									@endcan
									
									@can('delete', $project)
										<button type="button" data-route="{{ route('api.projects.delete', $project->id) }}" class="action-btn delete" title="Delete">
											<i class="material-icons">delete</i>
										</button>
									@endcan

									<a href="{{ route('projects.show', $project->id) }}" class="action-btn view" title="View"><i class="material-icons">visibility</i></a>
								</div>
							</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $projects->links() }}
        </div>
    </div>        
</div>
@push('scripts')
    <script>
        $(document).ready(function() {
            $(document).on('click', '.delete', function (e) {
                e.preventDefault();
                
                const self = $(this)
                const URL = self.data('route')
                
                $.ajax({
                    url: URL,
                    type: 'DELETE',
                    dataType: 'JSON',
                    success: function (response) {
                        successToast(response.message);
                        window.location.href = "{{ route('projects.index') }}";
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
