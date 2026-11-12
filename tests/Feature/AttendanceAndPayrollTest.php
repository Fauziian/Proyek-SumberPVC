<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\BarangPvc;
use App\Models\Karyawan;
use App\Models\KomponenGaji;
use App\Models\SettingGaji;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAndPayrollTest extends TestCase
{
    use RefreshDatabase;

    private function hrUser(): User
    {
        return User::create([
            'nama' => 'HR Staff Test',
            'username' => 'hr.tester',
            'password' => 'password',
            'role' => 'hr',
            'is_active' => true,
        ]);
    }

    private function adminUser(): User
    {
        return User::create([
            'nama' => 'Admin Test',
            'username' => 'admin.tester',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        SettingGaji::create([
            'gaji_pokok_kategori_a'  => 0,
            'gaji_pokok_kategori_b'  => 0,
            'tarif_per_jam_kategori_a' => 12000,
            'tarif_per_jam_kategori_b' => 17000,
            'insentif_jam_lebih_pct' => 0,
            'insentif_lembur_pct'    => 0,
            'potongan_setengah_pct'  => 0,
            'is_active'              => true,
            'keterangan'             => 'Tarif default',
        ]);
    }

    public function test_attendance_check_in_and_check_out_success(): void
    {
        $karyawan = Karyawan::create([
            'nama' => 'Budi Worker',
            'jabatan' => 'Operator',
            'tanggal_masuk' => '2023-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);

        $hr = $this->hrUser();

        // 1. Check in
        $response = $this->actingAs($hr)->post(route('attendance.check-in'), [
            'id_karyawan' => $karyawan->id,
            'tanggal' => now()->toDateString(),
            'jam_masuk' => '08:00',
        ]);
        $response->assertSessionHasNoErrors();

        $absensi = Absensi::where('id_karyawan', $karyawan->id)->first();
        $this->assertNotNull($absensi);
        $this->assertFalse((bool)$absensi->sudah_pulang);

        // 2. Check out
        $responseOut = $this->actingAs($hr)->put(route('attendance.check-out', $absensi->id), [
            'durasi_jam' => 8,
            'jam_keluar' => '16:00',
        ]);
        $responseOut->assertSessionHasNoErrors();

        $absensi->refresh();
        $this->assertTrue((bool)$absensi->sudah_pulang);
        $this->assertEquals(8, $absensi->durasi_jam);
        $this->assertSame('Penuh', $absensi->status_kehadiran);
    }

    public function test_payroll_generation_respects_tenure_category_and_protects_final(): void
    {
        $karyawanA = Karyawan::create([
            'nama' => 'Karyawan Junior',
            'jabatan' => 'Operator',
            'tanggal_masuk' => '2024-01-01', // < 5 tahun -> Kategori A (12.000)
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);

        $periode = '2026-09';

        // Rekam absensi 8 jam
        Absensi::create([
            'id_karyawan' => $karyawanA->id,
            'tanggal' => '2026-09-10',
            'jam_masuk' => '08:00',
            'jam_keluar' => '16:00',
            'durasi_jam' => 8,
            'status_kehadiran' => 'Penuh',
            'sudah_pulang' => true,
        ]);

        $hr = $this->hrUser();

        // Generate payroll
        $response = $this->actingAs($hr)->post(route('payroll.calculate'), [
            'periode' => $periode,
        ]);
        $response->assertSessionHasNoErrors();

        $slip = KomponenGaji::where('id_karyawan', $karyawanA->id)->where('periode', $periode)->first();
        $this->assertNotNull($slip);
        $this->assertSame('draft', $slip->status);
        // 8 jam * 12.000 = 96.000
        $this->assertEquals(96000, $slip->total_gaji);

        // Finalize slip
        $responseFinal = $this->actingAs($hr)->put(route('payroll.finalize', $slip->id));
        $responseFinal->assertSessionHasNoErrors();

        $slip->refresh();
        $this->assertSame('final', $slip->status);

        // Coba generate ulang periode yang sama: slip final tidak boleh ditimpa (K-02)
        $this->actingAs($hr)->post(route('payroll.calculate'), [
            'periode' => $periode,
        ]);

        $slip->refresh();
        $this->assertSame('final', $slip->status);
    }

    public function test_stock_date_queries_include_sunday_and_end_of_month(): void
    {
        $barang = BarangPvc::create([
            'nama_barang' => 'Outsole Pria',
            'kode_barang' => 'OS-TEST-SUN',
            'kategori' => 'Outsole',
            'jenis' => 'Outsole Pria',
            'warna' => null,
            'satuan' => 'kodi',
            'stok_minimum' => 10,
            'stok_saat_ini' => 100,
        ]);

        // Minggu 2026-09-06
        BarangMasuk::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-09-06',
            'jumlah' => 7,
            'keterangan' => 'Mingguan',
        ]);

        // Akhir bulan 2026-09-30
        BarangMasuk::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-09-30',
            'jumlah' => 5,
            'keterangan' => 'Akhir bulan',
        ]);

        // Transaksi keluar di akhir bulan
        BarangKeluar::create([
            'id_barang' => $barang->id,
            'tanggal' => '2026-09-30',
            'jumlah' => 2,
            'tujuan_penggunaan' => 'Pelanggan Akhir Bulan',
        ]);

        // Cek query range mingguan (termasuk Minggu)
        $masukMinggu = BarangMasuk::where('id_barang', $barang->id)
            ->whereDate('tanggal', '>=', '2026-08-31')
            ->whereDate('tanggal', '<=', '2026-09-06')
            ->sum('jumlah');

        $this->assertEquals(7, $masukMinggu);

        // Cek query range bulanan (termasuk tanggal 30)
        $masukBulan = BarangMasuk::where('id_barang', $barang->id)
            ->whereDate('tanggal', '>=', '2026-09-01')
            ->whereDate('tanggal', '<=', '2026-09-30')
            ->sum('jumlah');

        $this->assertEquals(12, $masukBulan); // 7 + 5

        $keluarBulan = BarangKeluar::where('id_barang', $barang->id)
            ->whereDate('tanggal', '>=', '2026-09-01')
            ->whereDate('tanggal', '<=', '2026-09-30')
            ->sum('jumlah');

        $this->assertEquals(2, $keluarBulan);
    }
}
