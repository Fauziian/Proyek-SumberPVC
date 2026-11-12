<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\KomponenGaji;
use App\Models\BarangPvc;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use App\Models\NotifikasiStok;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Tampilkan Dashboard utama berdasarkan Role Pengguna.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $role = $user->role;

        // Data yang dikirim disesuaikan dengan role
        $data = [
            'role' => $role,
            'stats' => [],
        ];

        // 1. Data Umum untuk Dashboard
        if ($role === 'admin') {
            $totalGaji = KomponenGaji::where('periode', Carbon::now()->format('Y-m'))->sum('total_gaji');
            if ($totalGaji == 0) {
                $totalGaji = KomponenGaji::where('periode', Carbon::now()->subMonth()->format('Y-m'))->sum('total_gaji');
            }

            $data['stats'] = [
                'total_karyawan' => Karyawan::where('is_active', true)->count(),
                'hadir_hari_ini' => Absensi::whereDate('tanggal', now()->toDateString())->count(),
                'total_stok' => BarangPvc::sum('stok_saat_ini'),
                'total_gaji_bulan_ini' => $totalGaji,
                'recent_logins' => User::whereNotNull('last_login_at')
                    ->orderBy('last_login_at', 'desc')
                    ->take(5)
                    ->get(['nama', 'username', 'role', 'last_login_at']),
            ];
        } 
        
        elseif ($role === 'hr') {
            $currentMonth = Carbon::now()->format('Y-m');
            $data['stats'] = [
                'total_karyawan' => Karyawan::where('is_active', true)->count(),
                'karyawan_nonaktif' => Karyawan::where('is_active', false)->count(),
                'hadir_hari_ini' => Absensi::whereDate('tanggal', now()->toDateString())->count(),
                'sedang_bekerja' => Absensi::whereDate('tanggal', now()->toDateString())
                    ->where('sudah_pulang', false)
                    ->count(),
                'setengah_hari_ini' => Absensi::whereDate('tanggal', now()->toDateString())
                    ->where('status_kehadiran', 'Setengah Hari')
                    ->where('sudah_pulang', true)
                    ->count(),
                'total_payroll_bulan_lalu' => KomponenGaji::where('periode', Carbon::now()->subMonth()->format('Y-m'))
                    ->sum('total_gaji'),
            ];

            // Rincian absensi terbaru
            $data['recent_absensi'] = Absensi::with('karyawan:id,nama,jabatan')
                ->orderBy('tanggal', 'desc')
                ->take(5)
                ->get();
        } 
        
        elseif ($role === 'warehouse') {
            $data['stats'] = [
                'total_jenis_barang' => BarangPvc::count(),
                'stok_aman' => BarangPvc::whereRaw('stok_saat_ini > (stok_minimum * 1.3)')->count(),
                'stok_menipis' => BarangPvc::whereRaw('stok_saat_ini > stok_minimum AND stok_saat_ini <= (stok_minimum * 1.3)')->count(),
                'stok_kritis' => BarangPvc::whereRaw('stok_saat_ini <= stok_minimum')->count(),
            ];

            $data['notifikasi_kritis'] = NotifikasiStok::with('barangPvc')
                ->where('is_read', false)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Ringkasan stok fisik yang dapat langsung dilihat petugas gudang.
            $data['stok_tersedia'] = BarangPvc::query()
                ->orderByRaw('CASE WHEN stok_saat_ini > 0 THEN 0 ELSE 1 END')
                ->orderByDesc('stok_saat_ini')
                ->orderBy('kategori')
                ->orderBy('jenis')
                ->orderBy('warna')
                ->get(['id', 'kategori', 'jenis', 'warna', 'stok_saat_ini', 'stok_minimum', 'satuan']);

            // Riwayat transaksi gudang terbaru
            $data['recent_masuk'] = BarangMasuk::with('barangPvc')
                ->orderBy('tanggal', 'desc')
                ->take(3)
                ->get();
                
            $data['recent_keluar'] = BarangKeluar::with('barangPvc')
                ->orderBy('tanggal', 'desc')
                ->take(3)
                ->get();
        } 
        
        // Shared logs aktivitas
        $data['aktifitas_terbaru'] = $this->getRecentActivities($role);

        return Inertia::render('Dashboard', $data);
    }

    /**
     * Dapatkan log aktivitas terbaru secara global untuk feed dashboard.
     */
    private function getRecentActivities(string $role): array
    {
        $activities = [];

        // 1. Aktivitas Barang Masuk
        $masuk = BarangMasuk::with('barangPvc')->orderBy('created_at', 'desc')->take(3)->get();
        foreach ($masuk as $m) {
            $activities[] = [
                'id' => 'BM-' . $m->id,
                'type' => 'stock',
                'text' => "Hasil cetak {$m->barangPvc->jenis} warna {$m->barangPvc->warna} masuk {$m->jumlah} kodi",
                'time' => $m->created_at->diffForHumans(),
                'timestamp' => $m->created_at->timestamp,
            ];
        }

        // 2. Aktivitas Barang Keluar
        $keluar = BarangKeluar::with('barangPvc')->orderBy('created_at', 'desc')->take(3)->get();
        foreach ($keluar as $k) {
            $activities[] = [
                'id' => 'BK-' . $k->id,
                'type' => 'stock',
                'text' => "Pengiriman {$k->barangPvc->jenis} warna {$k->barangPvc->warna} sebanyak {$k->jumlah} kodi ke {$k->tujuan_penggunaan}",
                'time' => $k->created_at->diffForHumans(),
                'timestamp' => $k->created_at->timestamp,
            ];
        }

        // 3. Notifikasi Stok Baru
        $notif = NotifikasiStok::with('barangPvc')->orderBy('created_at', 'desc')->take(3)->get();
        foreach ($notif as $n) {
            $activities[] = [
                'id' => 'NTF-' . $n->id,
                'type' => 'alert',
                'text' => $n->pesan,
                'time' => $n->created_at->diffForHumans(),
                'timestamp' => $n->created_at->timestamp,
            ];
        }

        if ($role === 'warehouse') {
            usort($activities, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);
            return array_slice($activities, 0, 6);
        }

        // 4. Aktivitas Karyawan Baru
        $karyawan = Karyawan::orderBy('created_at', 'desc')->take(2)->get();
        foreach ($karyawan as $emp) {
            $activities[] = [
                'id' => 'EMP-' . $emp->id,
                'type' => 'employee',
                'text' => "Pendaftaran Karyawan Baru: {$emp->nama} sebagai {$emp->jabatan}",
                'time' => $emp->created_at->diffForHumans(),
                'timestamp' => $emp->created_at->timestamp,
            ];
        }

        // Urutkan berdasarkan waktu terbaru
        usort($activities, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return array_slice($activities, 0, 6);
    }
}
