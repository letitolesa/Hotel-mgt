<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('auth/login', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Protect authentication-related routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Users Management - Restricted by Spatie Permission
    Route::get('/users', function () {
        return Inertia::render('users/index'); 
    })->name('users.index')->middleware('can:view users');

    // Reports - Restricted by Spatie Permission
    Route::get('/reports', function () {
        return Inertia::render('reports/index');
    })->name('reports.index')->middleware('can:view reports');

    Route::get('/manage-accounts', function () {
        return Inertia::render('manage-accounts/index');
    })->name('manage-accounts.index')->middleware('can:manage-accounts');
});

// Protect the register route
Route::middleware('guest')->group(function () {
    Route::get('register', function () {
        return Inertia::render('auth/register'); // Registration view for guests
    })->name('register');
});

// Redirect authenticated users from /register
Route::middleware('auth')->group(function () {
    Route::get('register', function () {
        return redirect('/dashboard'); // Redirect authenticated users
    })->name('register');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/permissions', [App\Http\Controllers\Api\PermissionController::class, 'getUserPermissions']);
});

require __DIR__.'/settings.php';