<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Redirection root page ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Middleware auth dan active untuk semua route terautentikasi
Route::middleware(['auth', 'active'])->group(function () {
    // Dashboard umum (accessible untuk semua role terautentikasi dan aktif)
    Route::get('/dashboard', function (Request $request) {
        return Inertia::render('RoleDashboard', [
            'section' => 'general',
            'title' => 'Dashboard Utama',
            'role' => $request->user()->getRoleNames()->first() ?? $request->user()->role,
        ]);
    })->name('dashboard');

    // Route terproteksi Role: admin (Spatie RoleMiddleware)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/dashboard', function () {
            return Inertia::render('RoleDashboard', [
                'section' => 'admin',
                'title' => 'Area Administrator',
                'role' => 'admin',
            ]);
        })->name('admin.dashboard');
    });

    // Route terproteksi Role: hr (Spatie RoleMiddleware)
    Route::middleware(['role:hr'])->group(function () {
        Route::get('/hr/dashboard', function () {
            return Inertia::render('RoleDashboard', [
                'section' => 'hr',
                'title' => 'Area HR',
                'role' => 'hr',
            ]);
        })->name('hr.dashboard');
    });

    // Route terproteksi Role: warehouse / Gudang (Spatie RoleMiddleware)
    Route::middleware(['role:warehouse'])->group(function () {
        Route::get('/warehouse/dashboard', function () {
            return Inertia::render('RoleDashboard', [
                'section' => 'warehouse',
                'title' => 'Area Gudang',
                'role' => 'warehouse',
            ]);
        })->name('warehouse.dashboard');
    });
});

require __DIR__.'/auth.php';
