<?php

namespace Tests\Feature\Payroll;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\KomponenGaji;
use App\Models\SettingGaji;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * TASK-021 — Pengujian modul Payroll: kalkulasi gaji per jam, finalisasi slip, dan slip PDF.
 *
 * Aturan yang diuji (sesuai PayrollCalculationService):
 *  - gaji = jam normal x tarif + jam lembur x tarif (tarif sama, tanpa persentase lembur)
 *  - tarif per jam: kategori A Rp12.000, kategori B Rp17.000 (dari setting aktif)
 *  - hanya absensi yang sudah pulang dan berada di dalam periode yang dihitung
 *  - slip berstatus final tidak boleh ditimpa
 */
class PayrollManagementTest extends TestCase
{
    use RefreshDatabase;

    private const PERIODE = '2026-09';

    protected function setUp(): void
    {
        parent::setUp();

        // Halaman Inertia memuat aset Vite; manifest build tidak diperlukan saat tes.
        $this->withoutVite();

        SettingGaji::create([
            'gaji_pokok_kategori_a' => 0,
            'gaji_pokok_kategori_b' => 0,
            'tarif_per_jam_kategori_a' => 12000,
            'tarif_per_jam_kategori_b' => 17000,
            'insentif_jam_lebih_pct' => 0,
            'insentif_lembur_pct' => 0,
            'potongan_setengah_pct' => 0,
            'is_active' => true,
            'keterangan' => 'Tarif uji',
        ]);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function karyawan(array $overrides = []): Karyawan
    {
        return Karyawan::create(array_merge([
            'nama' => 'Budi',
            'jabatan' => 'Operator Produksi',
            'tanggal_masuk' => '2024-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ], $overrides));
    }

    private function absen(Karyawan $karyawan, string $tanggal, int $durasi, bool $sudahPulang = true): Absensi
    {
        $masuk = Carbon::createFromTime(8, 0);

        return Absensi::create([
            'id_karyawan' => $karyawan->id,
            'tanggal' => $tanggal,
            'jam_masuk' => '08:00',
            'jam_keluar' => $sudahPulang ? $masuk->copy()->addHours($durasi)->format('H:i') : '08:00',
            'durasi_jam' => $sudahPulang ? $durasi : 0,
            'jam_normal' => $sudahPulang ? min($durasi, 13) : 0,
            'jam_lembur' => $sudahPulang ? max($durasi - 13, 0) : 0,
            'status_kehadiran' => $durasi >= 14 ? 'Lembur' : ($durasi < 8 ? 'Setengah Hari' : 'Penuh'),
            'sudah_pulang' => $sudahPulang,
        ]);
    }

    private function hitung(User $hr, string $periode = self::PERIODE)
    {
        return $this->actingAs($hr)->post(route('payroll.calculate'), ['periode' => $periode]);
    }

    private function slip(Karyawan $karyawan, string $periode = self::PERIODE): KomponenGaji
    {
        return KomponenGaji::where('id_karyawan', $karyawan->id)->where('periode', $periode)->firstOrFail();
    }

    public function test_guest_is_redirected_and_only_hr_can_open_payroll(): void
    {
        $this->get(route('payroll.index'))->assertRedirect('/login');

        $this->actingAs($this->user('admin'))->get(route('payroll.index'))->assertForbidden();
        $this->actingAs($this->user('warehouse'))->get(route('payroll.index'))->assertForbidden();

        $this->actingAs($this->user('hr'))->get(route('payroll.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Payroll/Index'));
    }

    public function test_salary_is_normal_hours_plus_overtime_hours_at_the_same_hourly_rate(): void
    {
        $karyawan = $this->karyawan(); // kategori A = Rp12.000/jam
        $this->absen($karyawan, '2026-09-10', 8);   // 8 jam normal
        $this->absen($karyawan, '2026-09-11', 14);  // 13 jam normal + 1 jam lembur

        $this->hitung($this->user('hr'))->assertSessionHasNoErrors();

        $slip = $this->slip($karyawan);

        $this->assertSame('draft', $slip->status);
        $this->assertEquals(12000, $slip->tarif_per_jam);
        $this->assertEquals(21, $slip->total_jam_normal);
        $this->assertEquals(1, $slip->jam_lembur);
        $this->assertSame(2, $slip->hari_hadir);
        $this->assertEquals(252000, $slip->gaji_pokok);       // 21 x 12.000
        $this->assertEquals(12000, $slip->insentif_lembur);   // 1 x 12.000
        $this->assertEquals(264000, $slip->total_gaji);
    }

    public function test_category_b_uses_the_higher_hourly_rate(): void
    {
        $karyawan = $this->karyawan(['tanggal_masuk' => '2018-01-01', 'kategori_masa_kerja' => 'B']);
        $this->absen($karyawan, '2026-09-10', 10);

        $this->hitung($this->user('hr'));

        $slip = $this->slip($karyawan);
        $this->assertEquals(17000, $slip->tarif_per_jam);
        $this->assertEquals(170000, $slip->total_gaji); // 10 x 17.000
    }

    public function test_attendance_that_has_not_left_is_not_counted(): void
    {
        $karyawan = $this->karyawan();
        $this->absen($karyawan, '2026-09-10', 8);
        $this->absen($karyawan, '2026-09-11', 8, sudahPulang: false);

        $this->hitung($this->user('hr'));

        $slip = $this->slip($karyawan);
        $this->assertSame(1, $slip->hari_hadir);
        $this->assertEquals(96000, $slip->total_gaji);
    }

    public function test_only_attendance_inside_the_period_is_counted(): void
    {
        $karyawan = $this->karyawan();
        $this->absen($karyawan, '2026-08-31', 8);  // bulan sebelumnya
        $this->absen($karyawan, '2026-09-01', 8);  // awal periode
        $this->absen($karyawan, '2026-09-30', 8);  // akhir periode
        $this->absen($karyawan, '2026-10-01', 8);  // bulan berikutnya

        $this->hitung($this->user('hr'));

        $slip = $this->slip($karyawan);
        $this->assertSame(2, $slip->hari_hadir);
        $this->assertEquals(192000, $slip->total_gaji);
    }

    public function test_recalculating_a_draft_updates_the_same_slip_with_current_rates(): void
    {
        $hr = $this->user('hr');
        $karyawan = $this->karyawan();
        $this->absen($karyawan, '2026-09-10', 8);

        $this->hitung($hr);
        $this->assertEquals(96000, $this->slip($karyawan)->total_gaji);

        SettingGaji::aktif()->update(['tarif_per_jam_kategori_a' => 15000]);
        $this->absen($karyawan, '2026-09-11', 8);

        $this->hitung($hr);

        $this->assertSame(1, KomponenGaji::where('id_karyawan', $karyawan->id)->where('periode', self::PERIODE)->count());
        $slip = $this->slip($karyawan);
        $this->assertEquals(15000, $slip->tarif_per_jam);
        $this->assertEquals(240000, $slip->total_gaji); // 16 x 15.000
    }

    public function test_inactive_employee_is_processed_only_if_they_have_attendance_in_the_period(): void
    {
        $dengan = $this->karyawan(['nama' => 'Nonaktif Ada Absen', 'is_active' => false]);
        $tanpa = $this->karyawan(['nama' => 'Nonaktif Tanpa Absen', 'is_active' => false]);
        $aktif = $this->karyawan(['nama' => 'Aktif Tanpa Absen']);
        $this->absen($dengan, '2026-09-10', 8);

        $this->hitung($this->user('hr'));

        $this->assertDatabaseHas('komponen_gaji', ['id_karyawan' => $dengan->id, 'periode' => self::PERIODE]);
        $this->assertDatabaseHas('komponen_gaji', ['id_karyawan' => $aktif->id, 'periode' => self::PERIODE]);
        $this->assertDatabaseMissing('komponen_gaji', ['id_karyawan' => $tanpa->id, 'periode' => self::PERIODE]);
        $this->assertEquals(0, $this->slip($aktif)->total_gaji);
    }

    public function test_calculate_validates_period_format(): void
    {
        $hr = $this->user('hr');

        foreach (['September', '2026-13', '2026/09', ''] as $nilai) {
            $this->actingAs($hr)->post(route('payroll.calculate'), ['periode' => $nilai])
                ->assertSessionHasErrors('periode');
        }

        $this->assertSame(0, KomponenGaji::count());
    }

    public function test_calculate_without_any_employee_returns_an_error(): void
    {
        $this->hitung($this->user('hr'))->assertSessionHas('error');

        $this->assertSame(0, KomponenGaji::count());
    }

    public function test_hr_can_open_slip_detail(): void
    {
        $karyawan = $this->karyawan();
        $this->absen($karyawan, '2026-09-10', 8);
        $hr = $this->user('hr');
        $this->hitung($hr);
        $slip = $this->slip($karyawan);

        $this->actingAs($hr)->get(route('payroll.show', $slip))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Show')
                ->where('payroll.id', $slip->id)
                ->where('payroll.karyawan.nama', 'Budi'));
    }

    public function test_finalize_changes_status_once_and_rejects_second_attempt(): void
    {
        $karyawan = $this->karyawan();
        $this->absen($karyawan, '2026-09-10', 8);
        $hr = $this->user('hr');
        $this->hitung($hr);
        $slip = $this->slip($karyawan);

        $this->actingAs($hr)->put(route('payroll.finalize', $slip))->assertSessionHas('success');
        $this->assertSame('final', $slip->fresh()->status);

        $this->actingAs($hr)->put(route('payroll.finalize', $slip))->assertSessionHas('error');
    }

    public function test_recalculation_skips_final_slips_but_updates_draft_ones(): void
    {
        $hr = $this->user('hr');
        $final = $this->karyawan(['nama' => 'Slip Final']);
        $draft = $this->karyawan(['nama' => 'Slip Draft']);
        $this->absen($final, '2026-09-10', 8);
        $this->absen($draft, '2026-09-10', 8);
        $this->hitung($hr);

        $this->actingAs($hr)->put(route('payroll.finalize', $this->slip($final)));

        $this->absen($final, '2026-09-11', 8);
        $this->absen($draft, '2026-09-11', 8);

        $this->hitung($hr)->assertSessionHas('success', fn ($pesan) => str_contains($pesan, 'FINAL dilewati'));

        $this->assertEquals(96000, $this->slip($final)->total_gaji);   // tidak berubah
        $this->assertEquals(192000, $this->slip($draft)->total_gaji);  // dihitung ulang
    }

    public function test_hr_can_download_salary_slip_as_pdf(): void
    {
        $karyawan = $this->karyawan();
        $this->absen($karyawan, '2026-09-10', 8);
        $hr = $this->user('hr');
        $this->hitung($hr);

        $response = $this->actingAs($hr)->get(route('payroll.pdf', $this->slip($karyawan)));

        $response->assertOk();
        $this->assertStringContainsString('pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_non_hr_roles_cannot_calculate_finalize_or_download(): void
    {
        $karyawan = $this->karyawan();
        $this->absen($karyawan, '2026-09-10', 8);
        $this->hitung($this->user('hr'));
        $slip = $this->slip($karyawan);

        foreach (['admin', 'warehouse'] as $role) {
            $user = $this->user($role);

            $this->actingAs($user)->post(route('payroll.calculate'), ['periode' => '2026-10'])->assertForbidden();
            $this->actingAs($user)->put(route('payroll.finalize', $slip))->assertForbidden();
            $this->actingAs($user)->get(route('payroll.pdf', $slip))->assertForbidden();
            $this->actingAs($user)->get(route('payroll.show', $slip))->assertForbidden();
        }

        $this->assertSame('draft', $slip->fresh()->status);
    }
}
