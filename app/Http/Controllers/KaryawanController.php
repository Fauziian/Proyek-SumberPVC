<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rule;

class KaryawanController extends Controller
{
    /**
     * Tampilkan daftar karyawan.
     */
    public function index(Request $request): Response
    {
        $readOnly = $request->user()->role === 'admin';
        $query = Karyawan::with('pengguna');

        // Pencarian nama / jabatan
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%");
            });
        }

        // Filter status
        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Pagination
        $karyawan = $query->orderBy('nama', 'asc')->paginate(10)->withQueryString();

        // Ambil data users yang belum dikaitkan ke karyawan manapun
        // untuk opsi relasi saat membuat/mengedit data karyawan
        $linkedUserIds = Karyawan::whereNotNull('id_pengguna')->pluck('id_pengguna')->toArray();
        $availableUsers = $readOnly ? collect() : User::where('is_active', true)
            ->whereNotIn('id', $linkedUserIds)
            ->get(['id', 'nama', 'username', 'role']);

        return Inertia::render('Employees/Index', [
            'karyawan' => $karyawan,
            'filters' => $request->only(['search', 'status']),
            'availableUsers' => $availableUsers,
            'readOnly' => $readOnly,
            'indexRoute' => $readOnly ? 'admin.employees' : 'employees.index',
        ]);
    }

    /**
     * Simpan karyawan baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'tanggal_masuk' => ['required', 'date'],
            'kategori_masa_kerja' => ['required', 'in:A,B'],
            'is_active' => ['required', 'boolean'],
            'id_pengguna' => ['nullable', 'exists:users,id', 'unique:karyawan,id_pengguna'],
        ], [
            'id_pengguna.unique' => 'Pengguna sistem ini sudah dikaitkan dengan karyawan lain.',
        ]);

        Karyawan::create($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Data Karyawan berhasil ditambahkan.');
    }

    /**
     * Update data karyawan.
     */
    public function update(Request $request, Karyawan $employee): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'tanggal_masuk' => ['required', 'date'],
            'kategori_masa_kerja' => ['required', 'in:A,B'],
            'is_active' => ['required', 'boolean'],
            'id_pengguna' => [
                'nullable', 
                'exists:users,id', 
                Rule::unique('karyawan', 'id_pengguna')->ignore($employee->id)
            ],
        ], [
            'id_pengguna.unique' => 'Pengguna sistem ini sudah dikaitkan dengan karyawan lain.',
        ]);

        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Data Karyawan berhasil diperbarui.');
    }

    /**
     * Hapus data karyawan.
     */
    public function destroy(Karyawan $employee): RedirectResponse
    {
        // K-05: Cegah penghapusan jika karyawan memiliki riwayat absensi atau slip gaji
        if ($employee->absensi()->exists() || $employee->komponenGaji()->exists()) {
            return redirect()->back()
                ->with('error', 'Karyawan tidak dapat dihapus karena memiliki riwayat absensi atau slip gaji. Silakan nonaktifkan status karyawan.');
        }

        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Data Karyawan berhasil dihapus dari sistem.');
    }
}
