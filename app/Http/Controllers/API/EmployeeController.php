<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        $employees = User::with(['role:id,name,slug', 'department:id,name'])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($search) => $search->where('name', 'like', '%'.$request->query('search').'%')->orWhere('email', 'like', '%'.$request->query('search').'%')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->paginate(15)->withQueryString();

        return response()->json(['success' => true, 'data' => $employees]);
    }

    public function show(Request $request, User $employee)
    {
        $this->authorize('view', $employee);

        return response()->json(['success' => true, 'data' => $employee->load(['role:id,name,slug', 'department:id,name'])]);
    }

    public function store(EmployeeRequest $request)
    {
        $this->authorize('create', User::class);
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->is_active,
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'designation' => $request->designation,
            'phone' => $request->phone,
            'joined_at' => $request->joined_at,
            'created_by' => Auth::user()->id,
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->string('password')->toString());
        }
        // dd($data);

        try {
            User::create($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully',
        ], 201);
    }

    public function update(EmployeeRequest $request, $id)
    {
        $employee = User::findOrFail($id);
        $this->authorize('update', $employee);
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->is_active,
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'designation' => $request->designation,
            'phone' => $request->phone,
            'joined_at' => $request->joined_at,
            // 'created_by' => Auth::user()->id,
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->string('password')->toString());
        }

        try {
            $employee->update($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully',
        ]);
    }

    public function delete($id)
    {
        $employee = User::findOrFail($id);
        $this->authorize('delete', $employee);
        try {
            $employee->delete();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully',
        ]);
    }
}
