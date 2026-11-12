<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\KomponenGaji;
use App\Models\Karyawan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoWorkforceSeeder extends Seeder
{
    /**
     * Data contoh tenaga kerja pabrik:
     * 30 Karyawan, 2 HR, 2 Staff Gudang, dan 2 Admin.
     * Absensi dibuat dari awal bulan sampai kemarin agar hari ini tetap
     * dapat dipakai untuk mencoba tombol Masuk/Pulang.
     */
    public function run(): void
    {
        KomponenGaji::query()->delete();
        Absensi::query()->delete();
        Karyawan::query()->delete();

        $adminNina = User::updateOrCreate(
            ['username' => 'admin.nina'],
            [
                'nama' => 'Nina Pratiwi',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ],
        );

        $users = User::whereIn('username', ['admin', 'hr', 'hr.nurul', 'warehouse', 'wh.wahyu'])
            ->pluck('id', 'username');

        $data = [];
        $namaKaryawan = [
            'Agus Setiawan', 'Ahmad Fauzi', 'Aisyah Putri', 'Andi Kurniawan', 'Bayu Pratama',
            'Citra Lestari', 'Dedi Irawan', 'Dian Permata', 'Eko Saputra', 'Farah Nabila',
            'Fajar Ramadhan', 'Gilang Maulana', 'Hani Wulandari', 'Irfan Maulana', 'Joko Prasetyo',
            'Kartika Sari', 'Lina Marlina', 'Maya Indah', 'Nanda Prakoso', 'Novi Anggraini',
            'Rian Hidayat', 'Rina Wulandari', 'Rizky Firmansyah', 'Sari Puspita', 'Tono Wijaya',
            'Umar Faruq', 'Vina Oktaviani', 'Wawan Setiawan', 'Yani Kusuma', 'Yusuf Kurnia',
        ];

        foreach ($namaKaryawan as $index => $nama) {
            $tahunMasuk = $index % 3 === 0 ? 2019 : 2022;
            $data[] = [
                'nama' => $nama,
                'jabatan' => 'Karyawan',
                'tanggal_masuk' => sprintf('%04d-%02d-15', $tahunMasuk, ($index % 8) + 1),
                'kategori_masa_kerja' => $tahunMasuk <= 2021 ? 'B' : 'A',
                'is_active' => true,
            ];
        }

        $data = array_merge($data, [
            [
                'nama' => 'Siti Rahayu',
                'jabatan' => 'HR',
                'tanggal_masuk' => '2019-03-15',
                'kategori_masa_kerja' => 'B',
                'is_active' => true,
                'id_pengguna' => $users['hr'] ?? null,
            ],
            [
                'nama' => 'Nurul Fadilah',
                'jabatan' => 'HR',
                'tanggal_masuk' => '2022-09-05',
                'kategori_masa_kerja' => 'A',
                'is_active' => true,
                'id_pengguna' => $users['hr.nurul'] ?? null,
            ],
            [
                'nama' => 'Budi Santoso',
                'jabatan' => 'Staff Gudang',
                'tanggal_masuk' => '2018-11-20',
                'kategori_masa_kerja' => 'B',
                'is_active' => true,
                'id_pengguna' => $users['warehouse'] ?? null,
            ],
            [
                'nama' => 'Wahyu Setiawan',
                'jabatan' => 'Staff Gudang',
                'tanggal_masuk' => '2020-12-01',
                'kategori_masa_kerja' => 'B',
                'is_active' => true,
                'id_pengguna' => $users['wh.wahyu'] ?? null,
            ],
            [
                'nama' => 'Andi Pratama',
                'jabatan' => 'Admin',
                'tanggal_masuk' => '2018-01-10',
                'kategori_masa_kerja' => 'B',
                'is_active' => true,
                'id_pengguna' => $users['admin'] ?? null,
            ],
            [
                'nama' => 'Nina Pratiwi',
                'jabatan' => 'Admin',
                'tanggal_masuk' => '2023-02-01',
                'kategori_masa_kerja' => 'A',
                'is_active' => true,
                'id_pengguna' => $adminNina->id,
            ],
        ]);

        $karyawans = collect($data)->map(fn (array $karyawan) => Karyawan::create($karyawan));
        $mulai = Carbon::now('Asia/Jakarta')->startOfMonth();
        $selesai = Carbon::now('Asia/Jakarta')->subDay()->startOfDay();
        $durasiPilihan = [8, 10, 12, 14, 15];

        for ($tanggal = $mulai->copy(); $tanggal->lessThanOrEqualTo($selesai); $tanggal->addDay()) {
            foreach ($karyawans as $index => $karyawan) {
                $durasi = $durasiPilihan[($index + $tanggal->day) % count($durasiPilihan)];
                $lembur = $durasi >= 14;
                $jamMasuk = '08:00';
                $jamPulang = Carbon::createFromFormat('H:i', $jamMasuk)->addHours($durasi)->format('H:i');

                Absensi::create([
                    'id_karyawan' => $karyawan->id,
                    'tanggal' => $tanggal->toDateString(),
                    'jam_masuk' => $jamMasuk,
                    'jam_keluar' => $jamPulang,
                    'durasi_jam' => $durasi,
                    'jam_normal' => min($durasi, 13),
                    'jam_lembur' => max($durasi - 13, 0),
                    'status_kehadiran' => $lembur ? 'Lembur' : 'Penuh',
                    'sudah_pulang' => true,
                    'keterangan' => 'Contoh absensi bulan berjalan',
                ]);
            }
        }
    }
}
