<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * TASK-019 — Pengujian modul Admin: manajemen pengguna.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Halaman Inertia memuat aset Vite; manifest build tidak diperlukan saat tes.
        $this->withoutVite();
    }

    private function userWithRole(string $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => $role], $overrides));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('users.index'))->assertRedirect('/login');
    }

    public function test_only_admin_can_open_user_management(): void
    {
        foreach (['hr', 'warehouse'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('users.index'))
                ->assertForbidden();
        }

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Users/Index'));
    }

    public function test_user_list_can_be_searched_and_filtered(): void
    {
        $admin = $this->userWithRole('admin', ['nama' => 'Admin Utama', 'username' => 'admin_utama']);
        $this->userWithRole('hr', ['nama' => 'Sari HR', 'username' => 'sari_hr']);
        $this->userWithRole('warehouse', ['nama' => 'Budi Gudang', 'username' => 'budi_gudang', 'is_active' => false]);

        $this->actingAs($admin)->get(route('users.index', ['search' => 'sari']))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.username', 'sari_hr'));

        $this->actingAs($admin)->get(route('users.index', ['role' => 'warehouse']))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.username', 'budi_gudang'));

        $this->actingAs($admin)->get(route('users.index', ['status' => 'inactive']))
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.username', 'budi_gudang'));
    }

    public function test_admin_can_create_user_with_hashed_password_and_synced_role(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('users.store'), [
            'nama' => 'Dewi Payroll',
            'username' => 'dewi_hr',
            'password' => 'Sandi-Baru-123',
            'role' => 'hr',
            'is_active' => true,
        ])->assertRedirect(route('users.index'))->assertSessionHas('success');

        $created = User::where('username', 'dewi_hr')->firstOrFail();

        $this->assertSame('Dewi Payroll', $created->nama);
        $this->assertTrue(Hash::check('Sandi-Baru-123', $created->password));
        $this->assertNotSame('Sandi-Baru-123', $created->getRawOriginal('password'));
        $this->assertTrue($created->hasRole('hr'));
    }

    public function test_create_user_rejects_invalid_role_duplicate_username_and_weak_password(): void
    {
        $admin = $this->userWithRole('admin');
        $this->userWithRole('hr', ['username' => 'sudah_ada']);

        $this->actingAs($admin)->post(route('users.store'), [
            'nama' => 'Tamu',
            'username' => 'sudah_ada',
            'password' => '123',
            'role' => 'superuser',
            'is_active' => true,
        ])->assertSessionHasErrors(['username', 'password', 'role']);

        $this->assertDatabaseMissing('users', ['nama' => 'Tamu']);
    }

    public function test_admin_can_update_another_user_and_role_is_synced(): void
    {
        $admin = $this->userWithRole('admin');
        $target = $this->userWithRole('hr', ['username' => 'target_hr']);

        $this->actingAs($admin)->put(route('users.update', $target), [
            'nama' => 'Nama Baru',
            'username' => 'target_hr',
            'role' => 'warehouse',
            'is_active' => true,
        ])->assertRedirect(route('users.index'))->assertSessionHas('success');

        $target->refresh();

        $this->assertSame('Nama Baru', $target->nama);
        $this->assertTrue($target->hasRole('warehouse'));
        $this->assertFalse($target->hasRole('hr'));
    }

    public function test_admin_cannot_deactivate_or_change_role_of_own_account(): void
    {
        $admin = $this->userWithRole('admin', ['username' => 'admin_sendiri']);

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'nama' => $admin->nama,
            'username' => 'admin_sendiri',
            'role' => 'admin',
            'is_active' => false,
        ])->assertSessionHas('error');

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'nama' => $admin->nama,
            'username' => 'admin_sendiri',
            'role' => 'hr',
            'is_active' => true,
        ])->assertSessionHas('error');

        $admin->refresh();
        $this->assertTrue($admin->is_active);
        $this->assertSame('admin', $admin->role);
    }

    public function test_deactivating_a_user_ends_their_active_sessions(): void
    {
        $admin = $this->userWithRole('admin');
        $target = $this->userWithRole('warehouse', ['username' => 'target_gudang']);

        DB::table('sessions')->insert([
            'id' => 'sesi-target',
            'user_id' => $target->id,
            'payload' => 'x',
            'last_activity' => time(),
        ]);

        $this->actingAs($admin)->put(route('users.update', $target), [
            'nama' => $target->nama,
            'username' => 'target_gudang',
            'role' => 'warehouse',
            'is_active' => false,
        ])->assertSessionHas('success');

        $this->assertFalse($target->fresh()->is_active);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-target']);
    }

    public function test_admin_can_reset_password_of_another_user(): void
    {
        $admin = $this->userWithRole('admin');
        $target = $this->userWithRole('hr');

        $this->actingAs($admin)->put(route('users.reset-password', $target), [
            'password' => 'Reset-Sandi-456',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('Reset-Sandi-456', $target->fresh()->password));
    }

    public function test_admin_can_delete_other_user_but_not_own_account(): void
    {
        $admin = $this->userWithRole('admin');
        $target = $this->userWithRole('hr');

        $this->actingAs($admin)->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'))->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);

        $this->actingAs($admin)->delete(route('users.destroy', $admin))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_non_admin_cannot_modify_users(): void
    {
        $hr = $this->userWithRole('hr');
        $target = $this->userWithRole('warehouse');

        $this->actingAs($hr)->post(route('users.store'), [
            'nama' => 'X', 'username' => 'x_y', 'password' => 'Sandi-Baru-123', 'role' => 'admin', 'is_active' => true,
        ])->assertForbidden();
        $this->actingAs($hr)->delete(route('users.destroy', $target))->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('users', ['username' => 'x_y']);
    }
}
