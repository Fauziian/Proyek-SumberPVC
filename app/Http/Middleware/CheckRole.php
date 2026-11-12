<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: CheckRole
 * Implementasi RBAC nyata di level route/controller.
 *
 * Cara pakai di routes/web.php:
 *   Route::middleware(['auth', 'role:admin'])->group(...)
 *   Route::middleware(['auth', 'role:admin,hr'])->group(...)
 *
 * Middleware ini memeriksa role DARI DATABASE (via $request->user()->role),
 * BUKAN hanya dari state frontend — sehingga tidak bisa di-bypass dengan
 * manipulasi URL langsung.
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Pastikan user sudah login
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // Pastikan akun aktif
        if (! $request->user()->is_active) {
            abort(403, 'Akun Anda telah dinonaktifkan. Hubungi Administrator.');
        }

        // Cek apakah user memiliki salah satu role yang diizinkan
        if (! in_array($request->user()->role, $roles)) {
            // Return 403 JSON untuk request AJAX/Inertia, atau redirect untuk web biasa
            if ($request->expectsJson() || $request->header('X-Inertia')) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }

            return redirect()->route('dashboard')
                ->with('error', 'Anda tidak memiliki izin untuk mengakses halaman tersebut.');
        }

        return $next($request);
    }
}
