<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectAllocationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'login'])->middleware('guest')->name('login');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::prefix('departments')->as('departments.')->middleware('role:admin')->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
        Route::get('/create', [DepartmentController::class, 'create'])->name('create');
        Route::get('/edit/{id}', [DepartmentController::class, 'edit'])->name('edit');
    });
    Route::prefix('employees')->as('employees.')->middleware('role:admin,manager')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::get('/show/{id}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/edit/{id}', [EmployeeController::class, 'edit'])->name('edit');
    });
    Route::prefix('projects')->as('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
        Route::get('/edit/{id}', [ProjectController::class, 'edit'])->name('edit');
        Route::get('/show/{id}', [ProjectController::class, 'show'])->name('show');
    });
    Route::prefix('projectallocations')->as('projectallocations.')->group(function () {
        Route::get('/', [ProjectAllocationController::class, 'index'])->name('index');
        Route::get('/create', [ProjectAllocationController::class, 'create'])->name('create');
        Route::post('/', [ProjectAllocationController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [ProjectAllocationController::class, 'edit'])->name('edit');
        Route::put('/{allocation}', [ProjectAllocationController::class, 'update'])->name('update');
        Route::delete('/{allocation}', [ProjectAllocationController::class, 'destroy'])->name('destroy');
    });
    Route::post('/tasks/{task}/status', [TaskController::class, 'changeStatus'])->name('tasks.status');
    Route::resource('tasks', TaskController::class);
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
});
