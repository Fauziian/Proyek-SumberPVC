<?php

namespace Tests\Feature\Hr;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\KomponenGaji;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * TASK-020 — Pengujian modul HR: pencatatan absensi harian (WIB).
 *
 * Aturan yang diuji (sesuai AbsensiController):
 *  - durasi 1–7 jam  : Setengah Hari
 *  - durasi 8–13 jam : Penuh
 *  - durasi 14–15 jam: Lembur (jam normal maksimal 13, sisanya lembur)
 */
class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Halaman Inertia memuat aset Vite; manifest build tidak diperlukan saat tes.
        $this->withoutVite();
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function karyawan(array $overrides = []): Karyawan
    {
        return Karyawan::create(array_merge([
            'nama' => 'Budi Operator',
            'jabatan' => 'Operator Produksi',
            'tanggal_masuk' => '2023-01-01',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ], $overrides));
    }

    private function checkIn(User $hr, Karyawan $karyawan, ?string $tanggal = null, string $jam = '08:00')
    {
        return $this->actingAs($hr)->post(route('attendance.check-in'), [
            'id_karyawan' => $karyawan->id,
            'tanggal' => $tanggal ?? now()->toDateString(),
            'jam_masuk' => $jam,
        ]);
    }

    private function finalSlip(Karyawan $karyawan): void
    {
        KomponenGaji::create([
            'id_karyawan' => $karyawan->id,
            'periode' => now()->format('Y-m'),
            'status' => 'final',
        ]);
    }

    public static function durasiProvider(): array
    {
        return [
            '4 jam setengah hari' => [4, 'Setengah Hari', 4, 0, '12:00'],
            '8 jam penuh' => [8, 'Penuh', 8, 0, '16:00'],
            '13 jam penuh' => [13, 'Penuh', 13, 0, '21:00'],
            '14 jam lembur' => [14, 'Lembur', 13, 1, '22:00'],
            '15 jam lembur' => [15, 'Lembur', 13, 2, '23:00'],
        ];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('attendance.index'))->assertRedirect('/login');
    }

    public function test_only_hr_can_open_attendance_page(): void
    {
        $this->actingAs($this->user('admin'))->get(route('attendance.index'))->assertForbidden();
        $this->actingAs($this->user('warehouse'))->get(route('attendance.index'))->assertForbidden();

        $this->actingAs($this->user('hr'))->get(route('attendance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Attendance/Index'));
    }

    public function test_attendance_page_lists_only_active_employees_with_stats(): void
    {
        $aktif = $this->karyawan(['nama' => 'Aktif Hadir']);
        $nonaktif = $this->karyawan(['nama' => 'Tidak Aktif', 'is_active' => false]);
        $hr = $this->user('hr');

        $this->checkIn($hr, $aktif);

        $this->actingAs($hr)->get(route('attendance.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', Karyawan::where('is_active', true)->count())
                ->where('stats.sudah_masuk', 1)
                ->where('rows', function ($rows) use ($aktif, $nonaktif) {
                    $ids = collect($rows)->pluck('id_karyawan');

                    return $ids->contains($aktif->id) && ! $ids->contains($nonaktif->id);
                }));
    }

    public function test_check_in_records_attendance_as_not_yet_left(): void
    {
        $karyawan = $this->karyawan();

        $this->checkIn($this->user('hr'), $karyawan, null, '07:30')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $absensi = Absensi::where('id_karyawan', $karyawan->id)->firstOrFail();

        $this->assertSame('07:30', substr($absensi->jam_masuk, 0, 5));
        $this->assertFalse((bool) $absensi->sudah_pulang);
        $this->assertSame('Setengah Hari', $absensi->status_kehadiran);
        $this->assertSame(now()->toDateString(), $absensi->tanggal->toDateString());
    }

    public function test_check_in_twice_on_same_day_does_not_create_duplicate(): void
    {
        $hr = $this->user('hr');
        $karyawan = $this->karyawan();

        $this->checkIn($hr, $karyawan);
        $this->checkIn($hr, $karyawan, null, '09:00')->assertSessionHas('success');

        $this->assertSame(1, Absensi::where('id_karyawan', $karyawan->id)->count());
    }

    public function test_check_in_rejects_future_date_unknown_employee_and_bad_time(): void
    {
        $hr = $this->user('hr');
        $karyawan = $this->karyawan();

        $this->checkIn($hr, $karyawan, now()->addDay()->toDateString())->assertSessionHasErrors('tanggal');

        $this->actingAs($hr)->post(route('attendance.check-in'), [
            'id_karyawan' => 999999,
            'tanggal' => now()->toDateString(),
            'jam_masuk' => '08:00',
        ])->assertSessionHasErrors('id_karyawan');

        $this->checkIn($hr, $karyawan, null, '8 pagi')->assertSessionHasErrors('jam_masuk');

        $this->assertSame(0, Absensi::count());
    }

    public function test_check_in_rejects_inactive_employee(): void
    {
        $nonaktif = $this->karyawan(['is_active' => false]);

        $this->checkIn($this->user('hr'), $nonaktif)->assertSessionHas('error');

        $this->assertSame(0, Absensi::count());
    }

    #[DataProvider('durasiProvider')]
    public function test_check_out_computes_status_normal_and_overtime_hours(
        int $durasi,
        string $status,
        int $jamNormal,
        int $jamLembur,
        string $jamKeluar,
    ): void {
        $hr = $this->user('hr');
        $karyawan = $this->karyawan();
        $this->checkIn($hr, $karyawan, null, '08:00');
        $absensi = Absensi::where('id_karyawan', $karyawan->id)->firstOrFail();

        $this->actingAs($hr)->put(route('attendance.check-out', $absensi), ['durasi_jam' => $durasi])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $absensi->refresh();

        $this->assertTrue((bool) $absensi->sudah_pulang);
        $this->assertSame($status, $absensi->status_kehadiran);
        $this->assertEquals($durasi, $absensi->durasi_jam);
        $this->assertEquals($jamNormal, $absensi->jam_normal);
        $this->assertEquals($jamLembur, $absensi->jam_lembur);
        $this->assertSame($jamKeluar, substr($absensi->jam_keluar, 0, 5));
    }

    public function test_check_out_validates_duration_range(): void
    {
        $hr = $this->user('hr');
        $karyawan = $this->karyawan();
        $this->checkIn($hr, $karyawan);
        $absensi = Absensi::where('id_karyawan', $karyawan->id)->firstOrFail();

        foreach ([0, 16, 'abc'] as $nilai) {
            $this->actingAs($hr)->put(route('attendance.check-out', $absensi), ['durasi_jam' => $nilai])
                ->assertSessionHasErrors('durasi_jam');
        }

        $this->assertFalse((bool) $absensi->fresh()->sudah_pulang);
    }

    public function test_check_out_cannot_be_done_twice(): void
    {
        $hr = $this->user('hr');
        $karyawan = $this->karyawan();
        $this->checkIn($hr, $karyawan);
        $absensi = Absensi::where('id_karyawan', $karyawan->id)->firstOrFail();

        $this->actingAs($hr)->put(route('attendance.check-out', $absensi), ['durasi_jam' => 8]);
        $this->actingAs($hr)->put(route('attendance.check-out', $absensi), ['durasi_jam' => 14])
            ->assertSessionHas('error');

        $this->assertEquals(8, $absensi->fresh()->durasi_jam);
    }

    public function test_hr_can_delete_attendance_record(): void
    {
        $hr = $this->user('hr');
        $karyawan = $this->karyawan();
        $this->checkIn($hr, $karyawan);
        $absensi = Absensi::where('id_karyawan', $karyawan->id)->firstOrFail();

        $this->actingAs($hr)->delete(route('attendance.destroy', $absensi))->assertSessionHas('success');

        $this->assertDatabaseMissing('absensi', ['id' => $absensi->id]);
    }

    public function test_finalized_period_locks_check_in_check_out_and_delete(): void
    {
        $hr = $this->user('hr');
        $sudahAda = $this->karyawan(['nama' => 'Punya Absen']);
        $baru = $this->karyawan(['nama' => 'Belum Absen']);

        $this->checkIn($hr, $sudahAda);
        $absensi = Absensi::where('id_karyawan', $sudahAda->id)->firstOrFail();

        $this->finalSlip($sudahAda);
        $this->finalSlip($baru);

        $this->checkIn($hr, $baru)->assertSessionHas('error');
        $this->assertSame(0, Absensi::where('id_karyawan', $baru->id)->count());

        $this->actingAs($hr)->put(route('attendance.check-out', $absensi), ['durasi_jam' => 8])
            ->assertSessionHas('error');
        $this->assertFalse((bool) $absensi->fresh()->sudah_pulang);

        $this->actingAs($hr)->delete(route('attendance.destroy', $absensi))->assertSessionHas('error');
        $this->assertDatabaseHas('absensi', ['id' => $absensi->id]);
    }

    public function test_non_hr_roles_cannot_record_attendance(): void
    {
        $karyawan = $this->karyawan();

        foreach (['admin', 'warehouse'] as $role) {
            $this->checkIn($this->user($role), $karyawan)->assertForbidden();
        }

        $this->assertSame(0, Absensi::count());
    }
}
