<?php

namespace Tests\Feature\Hr;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * TASK-020 — Pengujian modul HR: data master karyawan.
 */
class EmployeeManagementTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Siti Pengemas',
            'jabatan' => 'Pengemasan',
            'tanggal_masuk' => '2024-03-15',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('employees.index'))->assertRedirect('/login');
    }

    public function test_hr_can_open_employee_page_with_edit_access(): void
    {
        $this->actingAs($this->user('hr'))->get(route('employees.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employees/Index')
                ->where('readOnly', false)
                ->where('indexRoute', 'employees.index'));
    }

    public function test_admin_can_only_view_employees_in_read_only_mode(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('admin.employees'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employees/Index')
                ->where('readOnly', true)
                ->where('indexRoute', 'admin.employees'));

        $this->actingAs($admin)->post(route('employees.store'), $this->payload())->assertForbidden();
        $this->assertDatabaseMissing('karyawan', ['nama' => 'Siti Pengemas']);
    }

    public function test_other_roles_cannot_open_employee_pages(): void
    {
        $this->actingAs($this->user('warehouse'))->get(route('employees.index'))->assertForbidden();
        $this->actingAs($this->user('warehouse'))->get(route('admin.employees'))->assertForbidden();
        $this->actingAs($this->user('hr'))->get(route('admin.employees'))->assertForbidden();
    }

    public function test_hr_can_create_employee(): void
    {
        $this->actingAs($this->user('hr'))->post(route('employees.store'), $this->payload())
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('karyawan', [
            'nama' => 'Siti Pengemas',
            'jabatan' => 'Pengemasan',
            'kategori_masa_kerja' => 'A',
            'is_active' => true,
        ]);
    }

    public function test_create_employee_validates_required_fields_and_category(): void
    {
        $hr = $this->user('hr');

        $this->actingAs($hr)->post(route('employees.store'), [
            'nama' => '',
            'jabatan' => '',
            'tanggal_masuk' => 'bukan-tanggal',
            'kategori_masa_kerja' => 'C',
            'is_active' => true,
        ])->assertSessionHasErrors(['nama', 'jabatan', 'tanggal_masuk', 'kategori_masa_kerja']);

        $this->actingAs($hr)->post(route('employees.store'), $this->payload(['id_pengguna' => 999999]))
            ->assertSessionHasErrors('id_pengguna');
    }

    public function test_one_system_account_cannot_be_linked_to_two_employees(): void
    {
        $hr = $this->user('hr');
        $akun = $this->user('warehouse');
        $this->karyawan(['nama' => 'Pemilik Akun', 'id_pengguna' => $akun->id]);

        $this->actingAs($hr)->post(route('employees.store'), $this->payload(['id_pengguna' => $akun->id]))
            ->assertSessionHasErrors('id_pengguna');

        $this->assertDatabaseMissing('karyawan', ['nama' => 'Siti Pengemas']);
    }

    public function test_hr_can_update_employee_and_keep_own_account_link(): void
    {
        $hr = $this->user('hr');
        $akun = $this->user('warehouse');
        $karyawan = $this->karyawan(['id_pengguna' => $akun->id]);

        $this->actingAs($hr)->put(route('employees.update', $karyawan), $this->payload([
            'nama' => 'Budi Senior',
            'jabatan' => 'Kepala Regu',
            'kategori_masa_kerja' => 'B',
            'is_active' => false,
            'id_pengguna' => $akun->id,
        ]))->assertRedirect(route('employees.index'))->assertSessionHas('success');

        $karyawan->refresh();
        $this->assertSame('Budi Senior', $karyawan->nama);
        $this->assertSame('B', $karyawan->kategori_masa_kerja);
        $this->assertFalse($karyawan->is_active);
        $this->assertSame($akun->id, $karyawan->id_pengguna);
    }

    public function test_employee_without_history_can_be_deleted(): void
    {
        $karyawan = $this->karyawan();

        $this->actingAs($this->user('hr'))->delete(route('employees.destroy', $karyawan))
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('karyawan', ['id' => $karyawan->id]);
    }

    public function test_employee_with_attendance_history_cannot_be_deleted(): void
    {
        $hr = $this->user('hr');
        $karyawan = $this->karyawan();

        $this->actingAs($hr)->post(route('attendance.check-in'), [
            'id_karyawan' => $karyawan->id,
            'tanggal' => now()->toDateString(),
            'jam_masuk' => '08:00',
        ])->assertSessionHasNoErrors();

        $this->actingAs($hr)->delete(route('employees.destroy', $karyawan))->assertSessionHas('error');

        $this->assertDatabaseHas('karyawan', ['id' => $karyawan->id]);
    }

    public function test_employee_list_can_be_searched_and_filtered_by_status(): void
    {
        $hr = $this->user('hr');
        $this->karyawan(['nama' => 'Zxq Aktif', 'jabatan' => 'Gudang']);
        $this->karyawan(['nama' => 'Zxq Nonaktif', 'jabatan' => 'Gudang', 'is_active' => false]);

        $this->actingAs($hr)->get(route('employees.index', ['search' => 'Zxq']))
            ->assertInertia(fn (Assert $page) => $page->has('karyawan.data', 2));

        $this->actingAs($hr)->get(route('employees.index', ['search' => 'Zxq', 'status' => 'inactive']))
            ->assertInertia(fn (Assert $page) => $page->has('karyawan.data', 1)->where('karyawan.data.0.nama', 'Zxq Nonaktif'));

        $this->actingAs($hr)->get(route('employees.index', ['search' => 'Zxq', 'status' => 'active']))
            ->assertInertia(fn (Assert $page) => $page->has('karyawan.data', 1)->where('karyawan.data.0.nama', 'Zxq Aktif'));
    }

    public function test_available_accounts_exclude_already_linked_and_inactive_users(): void
    {
        $hr = $this->user('hr');
        $terhubung = $this->user('warehouse');
        $bebas = $this->user('warehouse');
        $nonaktif = User::factory()->create(['role' => 'warehouse', 'is_active' => false]);
        $this->karyawan(['id_pengguna' => $terhubung->id]);

        $this->actingAs($hr)->get(route('employees.index'))
            ->assertInertia(fn (Assert $page) => $page->where('availableUsers', function ($users) use ($terhubung, $bebas, $nonaktif) {
                $ids = collect($users)->pluck('id');

                return $ids->contains($bebas->id)
                    && ! $ids->contains($terhubung->id)
                    && ! $ids->contains($nonaktif->id);
            }));
    }
}
