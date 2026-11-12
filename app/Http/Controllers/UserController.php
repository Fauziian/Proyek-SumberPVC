<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Tampilkan daftar user sistem.
     */
    public function index(Request $request): Response
    {
        $query = User::query();

        // Pencarian
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filter role
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // Filter status
        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->orderBy('nama', 'asc')->paginate(10)->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'unique:users,username', 'max:50', 'alpha_dash'],
            'password' => ['required', 'string', Password::defaults()],
            'role' => ['required', 'in:admin,hr,warehouse'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'Akun Pengguna berhasil ditambahkan.');
    }

    /**
     * Update data user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 
                'string', 
                'max:50', 
                'alpha_dash', 
                Rule::unique('users', 'username')->ignore($user->id)
            ],
            'role' => ['required', 'in:admin,hr,warehouse'],
            'is_active' => ['required', 'boolean'],
        ]);

        // Cegah admin menonaktifkan dirinya sendiri
        if ($user->id === $request->user()->id && !$validated['is_active']) {
            return redirect()->back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        // Cegah admin mengubah role dirinya sendiri agar tidak terkunci keluar
        if ($user->id === $request->user()->id && $validated['role'] !== $user->role) {
            return redirect()->back()->with('error', 'Anda tidak dapat mengubah role Anda sendiri.');
        }

        $user->update($validated);

        // Putus sesi yang sedang aktif jika akun dinonaktifkan (T-03)
        if (!$user->is_active && Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return redirect()->route('users.index')
            ->with('success', 'Akun Pengguna berhasil diperbarui.');
    }

    /**
     * Reset password user.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Kata sandi berhasil di-reset.');
    }

    /**
     * Hapus user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Cegah admin menghapus dirinya sendiri
        if ($user->id === $request->user()->id) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Putuskan relasi Karyawan jika ada
        if ($user->karyawan()->exists()) {
            $user->karyawan()->update(['id_pengguna' => null]);
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Akun Pengguna berhasil dihapus dari sistem.');
    }
}
