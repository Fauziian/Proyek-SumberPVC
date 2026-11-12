<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\KaryawanController;
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

        // TASK-020 — Admin hanya melihat data karyawan (tanpa tambah/ubah/hapus)
        Route::get('/admin/employees', [KaryawanController::class, 'index'])->name('admin.employees');
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

        // TASK-020 — Modul HR: data master karyawan
        Route::get('/hr/employees', [KaryawanController::class, 'index'])->name('employees.index');
        Route::post('/hr/employees', [KaryawanController::class, 'store'])->name('employees.store');
        Route::put('/hr/employees/{employee}', [KaryawanController::class, 'update'])->name('employees.update');
        Route::delete('/hr/employees/{employee}', [KaryawanController::class, 'destroy'])->name('employees.destroy');

        // TASK-020 — Modul HR: pencatatan absensi harian (WIB)
        Route::get('/hr/attendance', [AbsensiController::class, 'index'])->name('attendance.index');
        Route::post('/hr/attendance/check-in', [AbsensiController::class, 'checkIn'])->name('attendance.check-in');
        Route::put('/hr/attendance/{attendance}/check-out', [AbsensiController::class, 'checkOut'])->name('attendance.check-out');
        Route::delete('/hr/attendance/{attendance}', [AbsensiController::class, 'destroy'])->name('attendance.destroy');

        // TASK-021 — Modul Payroll: kalkulasi gaji per jam, finalisasi, dan slip PDF
        Route::get('/hr/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/hr/payroll/calculate', [PayrollController::class, 'calculatePeriod'])->name('payroll.calculate');
        Route::get('/hr/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::put('/hr/payroll/{payroll}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');
        Route::get('/hr/payroll/{payroll}/pdf', [PayrollController::class, 'downloadPdf'])->name('payroll.pdf');
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
