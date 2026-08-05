<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\DirectoryContactController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DigitalProductController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/directory', [DirectoryController::class, 'index'])->name('directory.index');
    Route::resource('directory-contacts', DirectoryContactController::class)->except(['index', 'show']);
    Route::get('/settings', [UserController::class, 'settings'])->name('settings.edit');
    Route::put('/settings', [UserController::class, 'updateSettings'])->name('settings.update');

    // Assets
    Route::get('/assets/export', [AssetController::class, 'export'])->name('assets.export');
    Route::get('/assets/live', [AssetController::class, 'live'])->name('assets.live');
    Route::get('/assets/{asset}/label', [AssetController::class, 'label'])->name('assets.label');
    Route::patch('/assets/{asset}/status', [AssetController::class, 'updateStatus'])->name('assets.update-status');
    Route::delete('/assets/bulk-delete', [AssetController::class, 'bulkDestroy'])->name('assets.bulk-destroy');
    Route::delete('/assets/delete-all', [AssetController::class, 'destroyAll'])->name('assets.destroy-all');
    Route::resource('assets', AssetController::class);

    // Digital Products (licenses)
    Route::resource('digital-products', DigitalProductController::class);

    // Employees
    Route::get('/employees/template', [EmployeeController::class, 'downloadTemplate'])->name('employees.template');
    Route::patch('/employees/{employee}/status', [EmployeeController::class, 'updateStatus'])->name('employees.update-status');
    Route::post('/employees/import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::post('/employees/{employee}/documents', [EmployeeController::class, 'uploadDocument'])->name('employees.upload-document');
    Route::delete('/employees/{employee}/documents/{document}', [EmployeeController::class, 'deleteDocument'])->name('employees.delete-document');
    Route::get('/employees/{employee}/documents/{document}/download', [EmployeeController::class, 'downloadDocument'])->name('employees.download-document');
    Route::resource('employees', EmployeeController::class);

    // Staff/Admin routes
    Route::resource('departments', DepartmentController::class);
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::resource('brands', BrandController::class);
    Route::delete('/brands/bulk-delete', [BrandController::class, 'bulkDestroy'])->name('brands.bulk-destroy');
    Route::delete('/brands/delete-all', [BrandController::class, 'destroyAll'])->name('brands.destroy-all');
    Route::delete('/categories/bulk-delete', [CategoryController::class, 'bulkDestroy'])->name('categories.bulk-destroy');
    Route::delete('/categories/delete-all', [CategoryController::class, 'destroyAll'])->name('categories.destroy-all');
    Route::resource('categories', CategoryController::class);
    Route::delete('/locations/bulk-delete', [LocationController::class, 'bulkDestroy'])->name('locations.bulk-destroy');
    Route::delete('/locations/delete-all', [LocationController::class, 'destroyAll'])->name('locations.destroy-all');
    Route::resource('locations', LocationController::class);
    Route::resource('users', UserController::class);
});
