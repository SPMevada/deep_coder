<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DepartmentController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\ProjectAllocationController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\TaskController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::prefix('departments')->as('api.departments.')->middleware('role:admin')->group(function () {
        Route::post('/store', [DepartmentController::class, 'store'])->name('store');
        Route::put('/update/{id}', [DepartmentController::class, 'update'])->name('update');
        Route::delete('/delete/{id}', [DepartmentController::class, 'delete'])->name('delete');
    });
    Route::prefix('employees')->as('api.employees.')->middleware('role:admin,manager')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::put('/update/{id}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/delete/{id}', [EmployeeController::class, 'delete'])->name('delete');
    });
    Route::prefix('projects')->as('api.projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::match(['put', 'patch'], '/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project}', [ProjectController::class, 'delete'])->name('delete');
    });
    Route::apiResource('tasks', TaskController::class)->names('api.tasks');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'changeStatus'])->name('api.tasks.status');
    Route::get('/dashboard-summary', [DashboardController::class, 'dashboardSummary'])->name('api.dashboard-summary');
    Route::prefix('projectallocations')->as('api.projectallocations.')->group(function () {
        Route::post('/store', [ProjectAllocationController::class, 'store'])->name('store');
        Route::get('employees/{user}/availability', [ProjectAllocationController::class, 'availability'])->name('availability');
        Route::put('/update/{allocation}', [ProjectAllocationController::class, 'update'])->name('update');
        Route::delete('/delete/{id}', [ProjectAllocationController::class, 'delete'])->name('delete');
    });
});
