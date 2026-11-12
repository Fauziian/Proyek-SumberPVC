<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Inisialisasi role dan permission Spatie untuk 3 peran resmi: admin, hr, warehouse.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionsByRole = [
            'admin' => [
                'dashboard.view',
                'users.manage',
                'payroll.settings',
                'master-products.manage',
                'employees.view',
                'reports.view',
            ],
            'hr' => [
                'dashboard.view',
                'employees.manage',
                'attendance.manage',
                'payroll.manage',
                'reports.view',
            ],
            'warehouse' => [
                'dashboard.view',
                'stock.view',
                'stock.inbound',
                'stock.outbound',
                'stock.notifications',
                'reports.view',
            ],
        ];

        $allPermissions = collect($permissionsByRole)->flatten()->unique()->values();

        foreach ($allPermissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach ($permissionsByRole as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

