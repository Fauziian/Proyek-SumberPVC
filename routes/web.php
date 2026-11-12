<?php

use App\Http\Controllers\PayrollController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UserController;
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

        // TASK-019 — Modul Admin: manajemen pengguna
        Route::get('/admin/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/admin/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::put('/admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // TASK-019 — Modul Admin: master produk PVC (outsole, tali jepit, boloni gunung)
        Route::get('/admin/master-products', [StockController::class, 'master'])->name('admin.master-products');
        Route::post('/admin/master-products', [StockController::class, 'storeItem'])->name('admin.master-products.store');
        Route::put('/admin/master-products/{item}', [StockController::class, 'updateItem'])->name('admin.master-products.update');
        Route::delete('/admin/master-products/{item}', [StockController::class, 'destroyItem'])->name('admin.master-products.destroy');

        // TASK-019 — Modul Admin: konfigurasi tarif gaji
        Route::get('/admin/payroll-settings', [PayrollController::class, 'settings'])->name('payroll.settings');
        Route::post('/admin/payroll-settings', [PayrollController::class, 'updateSettings'])->name('payroll.settings.update');
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
