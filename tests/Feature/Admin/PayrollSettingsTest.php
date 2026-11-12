<?php

namespace Tests\Feature\Admin;

use App\Models\SettingGaji;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * TASK-019 — Pengujian modul Admin: konfigurasi tarif gaji per jam.
 */
class PayrollSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Halaman Inertia memuat aset Vite; manifest build tidak diperlukan saat tes.
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_only_admin_can_open_payroll_settings(): void
    {
        $this->get(route('payroll.settings'))->assertRedirect('/login');

        foreach (['hr', 'warehouse'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('payroll.settings'))
                ->assertForbidden();
        }

        $this->actingAs($this->admin())->get(route('payroll.settings'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Settings')
                ->has('settings')
                ->has('active_setting'));
    }

    public function test_update_creates_new_active_revision_and_keeps_history(): void
    {
        $lama = SettingGaji::aktif();

        $this->actingAs($this->admin())->post(route('payroll.settings.update'), [
            'tarif_per_jam_kategori_a' => 13000,
            'tarif_per_jam_kategori_b' => 18500,
            'keterangan' => 'Penyesuaian tarif',
        ])->assertSessionHas('success');

        $this->assertSame(2, SettingGaji::count());
        $this->assertSame(1, SettingGaji::where('is_active', true)->count());
        $this->assertFalse($lama->fresh()->is_active);

        $aktif = SettingGaji::aktif();
        $this->assertSame(13000, $aktif->tarif_per_jam_kategori_a);
        $this->assertSame(18500, $aktif->tarif_per_jam_kategori_b);
        $this->assertSame('Penyesuaian tarif', $aktif->keterangan);
    }

    public function test_update_rejects_negative_or_non_numeric_rates(): void
    {
        $lama = SettingGaji::aktif();

        $this->actingAs($this->admin())->post(route('payroll.settings.update'), [
            'tarif_per_jam_kategori_a' => -1,
            'tarif_per_jam_kategori_b' => 'abc',
        ])->assertSessionHasErrors(['tarif_per_jam_kategori_a', 'tarif_per_jam_kategori_b']);

        $this->assertSame(1, SettingGaji::count());
        $this->assertTrue($lama->fresh()->is_active);
    }

    public function test_non_admin_cannot_change_rates(): void
    {
        $lama = SettingGaji::aktif();

        $this->actingAs(User::factory()->create(['role' => 'hr']))
            ->post(route('payroll.settings.update'), [
                'tarif_per_jam_kategori_a' => 1,
                'tarif_per_jam_kategori_b' => 1,
            ])->assertForbidden();

        $this->assertSame(1, SettingGaji::count());
        $this->assertSame(12000, $lama->fresh()->tarif_per_jam_kategori_a);
    }
}
