<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $employees = User::whereHas('role', function ($query) {
                                $query->where('slug', '!=', 'admin');
                            })
                            ->with(['role:id,name', 'createdBy:id,created_by,name'])
                            ->when($request->filled('search'), fn ($query) => 
                                $query->where(fn ($search) => 
                                $search->where('name', 'like', '%'.$request->query('search').'%')
                                        ->orWhere('email', 'like', '%'.$request->query('search').'%'))
                            )
                            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
                            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
                            ->paginate(15)
                            ->withQueryString();

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('employees.index', compact('employees', 'departments'));
    }

    public function create()
    {
        $this->authorize('create', User::class);

        $roles = Role::where('slug', '!=', 'admin')->get();
        $departments = Department::get();

        return view('employees.create', compact('roles', 'departments'));
    }

    public function edit($id)
    {
        $employee = User::findOrFail($id);

        $this->authorize('update', $employee);

        $roles = Role::orderBy('name')->get(['id', 'name', 'slug']);
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('employees.edit', compact('employee', 'roles', 'departments'));
    }

    public function show($id)
    {
        $employee = User::findOrFail($id);

        $this->authorize('view', $employee);

        $employee->load('department:id,name', 'createdBy:id,name');

        return view('employees.view', compact('employee'));
    }
}
