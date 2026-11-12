<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\SettingGaji;
use App\Models\BarangPvc;
use App\Models\BarangMasuk;
use App\Models\BarangKeluar;
use App\Models\NotifikasiStok;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Setting Gaji (Konfigurasi Parameter Penggajian)
        // Tarif pabrik berdasarkan masa kerja karyawan.
        $settingGaji = SettingGaji::create([
            'gaji_pokok_kategori_a'  => 0,
            'gaji_pokok_kategori_b'  => 0,
            'tarif_per_jam_kategori_a' => 12000,
            'tarif_per_jam_kategori_b' => 17000,
            'insentif_jam_lebih_pct' => 0,
            'insentif_lembur_pct'    => 0,
            'potongan_setengah_pct'  => 0,
            'is_active'              => true,
            'keterangan'             => 'Tarif per jam: <5 tahun Rp12.000, ≥5 tahun Rp17.000.',
        ]);

        // 2. Seed Pengguna (Users) sesuai 3 Role Utama
        $usersData = [
            [
                'nama'      => 'Andi Pratama',
                'username'  => 'admin',
                'password'  => Hash::make('password'),
                'role'      => 'admin',
                'is_active' => true,
            ],
            [
                'nama'      => 'Siti Rahayu',
                'username'  => 'hr',
                'password'  => Hash::make('password'),
                'role'      => 'hr',
                'is_active' => true,
            ],
            [
                'nama'      => 'Budi Santoso',
                'username'  => 'warehouse',
                'password'  => Hash::make('password'),
                'role'      => 'warehouse',
                'is_active' => true,
            ],
            [
                'nama'      => 'Dewi Keuangan',
                'username'  => 'finance',
                'password'  => Hash::make('password'),
                'role'      => 'hr',
                'is_active' => true,
            ],
            [
                'nama'      => 'Nurul Fadilah',
                'username'  => 'hr.nurul',
                'password'  => Hash::make('password'),
                'role'      => 'hr',
                'is_active' => true,
            ],
            [
                'nama'      => 'Wahyu Setiawan',
                'username'  => 'wh.wahyu',
                'password'  => Hash::make('password'),
                'role'      => 'warehouse',
                'is_active' => true,
            ],
            [
                'nama'      => 'Bambang Sutrisno',
                'username'  => 'finance.bambang',
                'password'  => Hash::make('password'),
                'role'      => 'hr',
                'is_active' => false, // Nonaktifkan satu user HR/Keuangan untuk demonstrasi
            ]
        ];

        $users = [];
        foreach ($usersData as $u) {
            $users[$u['username']] = User::create($u);
        }

        // 3. Seed Karyawan (Tanpa field departemen, sesuai ERD)
        $karyawanData = [
            [
                'nama' => 'Ahmad Fauzi',
                'jabatan' => 'Operator Produksi',
                'tanggal_masuk' => '2019-03-15', // >= 5 tahun (Kategori B)
                'is_active' => true,
            ],
            [
                'nama' => 'Siti Rahayu',
                'jabatan' => 'Staf Administrasi HRD',
                'tanggal_masuk' => '2020-07-01', // >= 5 tahun (Kategori B)
                'is_active' => true,
                'username_linked' => 'hr',
            ],
            [
                'nama' => 'Budi Santoso',
                'jabatan' => 'Operator Gudang Utama',
                'tanggal_masuk' => '2018-11-20', // >= 5 tahun (Kategori B)
                'is_active' => true,
                'username_linked' => 'warehouse',
            ],
            [
                'nama' => 'Dewi Permata',
                'jabatan' => 'Supervisor Produksi',
                'tanggal_masuk' => '2017-05-10', // >= 5 tahun (Kategori B)
                'is_active' => true,
            ],
            [
                'nama' => 'Rini Wulandari',
                'jabatan' => 'Akuntan Keuangan',
                'tanggal_masuk' => '2021-01-15', // < 5 tahun (Kategori A)
                'is_active' => true,
            ],
            [
                'nama' => 'Joko Prasetyo',
                'jabatan' => 'Teknisi Mesin Outsole',
                'tanggal_masuk' => '2020-09-08', // >= 5 tahun (Kategori B)
                'is_active' => true,
            ],
            [
                'nama' => 'Maya Indah',
                'jabatan' => 'Staf Pengadaan Bahan Baku',
                'tanggal_masuk' => '2022-03-01', // < 5 tahun (Kategori A)
                'is_active' => true,
            ],
            [
                'nama' => 'Hendra Kurnia',
                'jabatan' => 'Quality Control Officer',
                'tanggal_masuk' => '2019-08-22', // >= 5 tahun (Kategori B)
                'is_active' => true,
            ],
            [
                'nama' => 'Lestari Dewi',
                'jabatan' => 'Operator Produksi Outsole',
                'tanggal_masuk' => '2021-06-14', // < 5 tahun (Kategori A)
                'is_active' => false, // Nonaktif
            ],
            [
                'nama' => 'Wahyu Setiawan',
                'jabatan' => 'Kepala Gudang',
                'tanggal_masuk' => '2016-12-01', // >= 5 tahun (Kategori B)
                'is_active' => true,
                'username_linked' => 'wh.wahyu',
            ],
            [
                'nama' => 'Nurul Fadilah',
                'jabatan' => 'Staf HR Karyawan',
                'tanggal_masuk' => '2022-09-05', // < 5 tahun (Kategori A)
                'is_active' => true,
                'username_linked' => 'hr.nurul',
            ],
            [
                'nama' => 'Agus Setiawan',
                'jabatan' => 'Driver Distribusi',
                'tanggal_masuk' => '2020-04-18', // >= 5 tahun (Kategori B)
                'is_active' => true,
            ],
        ];

        $karyawans = [];
        foreach ($karyawanData as $k) {
            $usernameLinked = $k['username_linked'] ?? null;
            unset($k['username_linked']);

            // Tentukan kategori masa kerja otomatis
            $years = Carbon::parse($k['tanggal_masuk'])->diffInYears(Carbon::now());
            $k['kategori_masa_kerja'] = $years >= 5 ? 'B' : 'A';

            if ($usernameLinked && isset($users[$usernameLinked])) {
                $k['id_pengguna'] = $users[$usernameLinked]->id;
            }

            $karyawans[] = Karyawan::create($k);
        }

        // 4. Seed Absensi (Bulan berjalan dan bulan lalu)
        // Kita seed data 22 hari kerja untuk Karyawan Aktif
        $startDate = Carbon::now()->subMonth()->startOfMonth(); // Bulan Lalu
        $endDate = Carbon::now()->subMonth()->endOfMonth();

        foreach ($karyawans as $emp) {
            if (!$emp->is_active) continue;

            $date = clone $startDate;
            $dayCount = 0;

            while ($date->lessThanOrEqualTo($endDate) && $dayCount < 22) {
                // Lewati akhir pekan (Sabtu & Minggu)
                if ($date->isWeekend()) {
                    $date->addDay();
                    continue;
                }

                $statusRandom = rand(1, 100);
                if ($statusRandom <= 5) {
                    // Setengah hari (< 8 jam, e.g. 5 jam)
                    $jamMasuk = '08:00:00';
                    $jamKeluar = '13:00:00';
                    $durasi = 5.00;
                    $status = 'Setengah Hari';
                } elseif ($statusRandom <= 15) {
                    // Jam Lebih (8-12 jam, e.g. 10 jam)
                    $jamMasuk = '08:00:00';
                    $jamKeluar = '18:00:00';
                    $durasi = 10.00;
                    $status = 'Jam Lebih';
                } elseif ($statusRandom <= 20) {
                    // Lembur (> 15 jam, e.g. 16 jam)
                    $jamMasuk = '08:00:00';
                    $jamKeluar = '00:00:00'; // keesokan harinya/lewat tengah malam
                    $durasi = 16.00;
                    $status = 'Lembur';
                } else {
                    // Normal
                    $jamMasuk = '08:00:00';
                    $jamKeluar = '17:00:00';
                    $durasi = 9.00; // termasuk 1 jam istirahat = 8 jam kerja bersih
                    $status = 'Penuh';
                }

                Absensi::create([
                    'id_karyawan' => $emp->id,
                    'tanggal' => $date->toDateString(),
                    'jam_masuk' => $jamMasuk,
                    'jam_keluar' => $jamKeluar,
                    'durasi_jam' => $durasi,
                    'status_kehadiran' => $status,
                    'keterangan' => 'Kehadiran Harian Terdata',
                ]);

                $date->addDay();
                $dayCount++;
            }
        }

        // 5. Katalog barang jadi. Jumlah disimpan dan ditampilkan dalam kodi.
        $barangData = [
            ['kode_barang' => 'TJ-PD-PTH', 'kategori' => 'Tali Jepit', 'jenis' => 'Pria Dewasa',   'warna' => 'Putih', 'stok_saat_ini' => 240],
            ['kode_barang' => 'TJ-PD-MRH', 'kategori' => 'Tali Jepit', 'jenis' => 'Pria Dewasa',   'warna' => 'Merah', 'stok_saat_ini' => 180],
            ['kode_barang' => 'TJ-PD-HTM', 'kategori' => 'Tali Jepit', 'jenis' => 'Pria Dewasa',   'warna' => 'Hitam', 'stok_saat_ini' => 210],
            ['kode_barang' => 'TJ-WD-PTH', 'kategori' => 'Tali Jepit', 'jenis' => 'Wanita Dewasa', 'warna' => 'Putih', 'stok_saat_ini' => 190],
            ['kode_barang' => 'TJ-WD-MRH', 'kategori' => 'Tali Jepit', 'jenis' => 'Wanita Dewasa', 'warna' => 'Merah', 'stok_saat_ini' => 170],
            ['kode_barang' => 'TJ-WD-HTM', 'kategori' => 'Tali Jepit', 'jenis' => 'Wanita Dewasa', 'warna' => 'Hitam', 'stok_saat_ini' => 160],
            ['kode_barang' => 'TJ-UP-BRU', 'kategori' => 'Tali Jepit', 'jenis' => 'Upin',           'warna' => 'Biru',  'stok_saat_ini' => 120],
            ['kode_barang' => 'TJ-IP-PNK', 'kategori' => 'Tali Jepit', 'jenis' => 'Ipin',           'warna' => 'Pink',  'stok_saat_ini' => 110],
            ['kode_barang' => 'OS-UTAMA', 'kategori' => 'Outsole', 'jenis' => 'Outsole', 'warna' => null, 'stok_saat_ini' => 120],
            ['kode_barang' => 'BG-MRH', 'kategori' => 'Boloni Gunung', 'jenis' => null, 'warna' => 'Merah', 'stok_saat_ini' => 80],
            ['kode_barang' => 'BG-PTH', 'kategori' => 'Boloni Gunung', 'jenis' => null, 'warna' => 'Putih', 'stok_saat_ini' => 100],
        ];

        $barangData = array_map(fn ($barang) => [
            ...$barang,
            'nama_barang' => $barang['kategori'] === 'Outsole'
                ? $barang['jenis']
                : ($barang['jenis'] ? "{$barang['jenis']} - {$barang['warna']}" : "{$barang['kategori']} - {$barang['warna']}"),
            'satuan' => 'kodi',
            'stok_minimum' => 25,
            'keterangan' => null,
        ], $barangData);

        $barangs = [];
        foreach ($barangData as $b) {
            $barang = BarangPvc::updateOrCreate(
                ['kode_barang' => $b['kode_barang']],
                $b
            );
            $barangs[$b['kode_barang']] = $barang;

            // Catat stok awal sebagai barang masuk agar mutasi sesuai stok fisik (T-08, R-03)
            if ($barang->stok_saat_ini > 0) {
                BarangMasuk::create([
                    'id_barang' => $barang->id,
                    'tanggal' => Carbon::now()->subDays(10)->toDateString(),
                    'jumlah' => $barang->stok_saat_ini,
                    'keterangan' => 'Stok awal sistem',
                ]);
            }
        }

        // 6. Contoh hasil cetak yang masuk ke gudang.
        $incomingData = [
            ['kode_barang' => 'TJ-PD-PTH', 'jumlah' => 100],
            ['kode_barang' => 'TJ-WD-MRH', 'jumlah' => 100],
            ['kode_barang' => 'TJ-UP-BRU', 'jumlah' => 50],
        ];

        foreach ($incomingData as $in) {
            $kb = $in['kode_barang'];
            unset($in['kode_barang']);
            $barang = $barangs[$kb];
            $in['id_barang'] = $barang->id;
            $in['tanggal'] = Carbon::now()->toDateString();
            $in['keterangan'] = 'Hasil cetak harian';
            BarangMasuk::create($in);
            $barang->increment('stok_saat_ini', $in['jumlah']);
        }

        // 7. Contoh pengiriman barang jadi kepada pelanggan.
        $outgoingData = [
            ['kode_barang' => 'TJ-PD-PTH', 'jumlah' => 40, 'tujuan_penggunaan' => 'Toko Sandal Jaya'],
            ['kode_barang' => 'TJ-WD-MRH', 'jumlah' => 30, 'tujuan_penggunaan' => 'Toko Sandal Jaya'],
        ];

        foreach ($outgoingData as $out) {
            $kb = $out['kode_barang'];
            unset($out['kode_barang']);
            $barang = $barangs[$kb];
            $out['id_barang'] = $barang->id;
            $out['tanggal'] = Carbon::now()->subDay()->toDateString();
            $out['keterangan'] = 'Pengiriman barang jadi';
            BarangKeluar::create($out);
            $barang->decrement('stok_saat_ini', $out['jumlah']);
        }
    }
}
