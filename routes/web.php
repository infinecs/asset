<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\RequestTicketController;
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

    // Assets
    Route::get('/assets/live', [AssetController::class, 'live'])->name('assets.live');
    Route::patch('/assets/{asset}/status', [AssetController::class, 'updateStatus'])->name('assets.update-status');
    Route::resource('assets', AssetController::class);

    // Request Tickets
    Route::patch('/tickets/{ticket}/status', [RequestTicketController::class, 'updateStatus'])->name('tickets.update-status');
    Route::resource('tickets', RequestTicketController::class);

    // Staff/Admin routes
    Route::resource('categories', CategoryController::class);
    Route::resource('locations', LocationController::class);
    Route::resource('users', UserController::class);
});
