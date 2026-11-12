<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_and_role_update_automatically_sync_spatie_role(): void
    {
        $user = User::factory()->create(['role' => 'hr']);

        $this->assertTrue($user->hasRole('hr'));
        $this->assertFalse($user->hasRole('admin'));

        $user->update(['role' => 'warehouse']);
        $user->refresh();

        $this->assertTrue($user->hasRole('warehouse'));
        $this->assertFalse($user->hasRole('hr'));
        $this->assertSame(['warehouse'], $user->getRoleNames()->values()->all());
    }

    public function test_role_permission_seeder_seeds_only_official_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $roles = Role::query()->orderBy('name')->pluck('name')->all();

        $this->assertSame(['admin', 'hr', 'warehouse'], $roles);
    }

    public function test_guests_are_redirected_to_login_from_protected_routes(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/hr/dashboard')->assertRedirect('/login');
        $this->get('/warehouse/dashboard')->assertRedirect('/login');
    }

    public function test_inactive_authenticated_user_is_logged_out_and_redirected(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_each_role_can_access_dashboard_and_its_allowed_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'warehouse']);

        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        $this->actingAs($hr)->get('/dashboard')->assertOk();
        $this->actingAs($hr)->get('/hr/dashboard')->assertOk();

        $this->actingAs($warehouse)->get('/dashboard')->assertOk();
        $this->actingAs($warehouse)->get('/warehouse/dashboard')->assertOk();
    }

    public function test_roles_are_forbidden_from_accessing_other_role_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $hr = User::factory()->create(['role' => 'hr']);
        $warehouse = User::factory()->create(['role' => 'warehouse']);

        $this->actingAs($admin)->get('/hr/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/warehouse/dashboard')->assertForbidden();

        $this->actingAs($hr)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($hr)->get('/warehouse/dashboard')->assertForbidden();

        $this->actingAs($warehouse)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($warehouse)->get('/hr/dashboard')->assertForbidden();
    }
}

