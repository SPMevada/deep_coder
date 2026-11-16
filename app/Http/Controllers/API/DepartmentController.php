<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function store(DepartmentRequest $request): JsonResponse
    {
        $data = ['name' => $request->name, 'created_by' => Auth::user()->id];

        Department::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Department created successfully',
        ], 201);
    }

    public function update(DepartmentRequest $request, $id): JsonResponse
    {
        $data = ['name' => $request->name, 'created_by' => Auth::user()->id];

        Department::where('id', $id)->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully',
        ], 201);
    }

    public function delete($id): JsonResponse
    {
        Department::where('id', $id)->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully',
        ], 201);
    }
}
