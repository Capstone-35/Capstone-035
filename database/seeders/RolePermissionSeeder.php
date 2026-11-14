<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'member.portal',
            'pos.access',
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.confirm_cash',
            'orders.cancel_unpaid',
            'orders.cancel_paid',
            'orders.handover',
            'kds.view',
            'kds.update_status',
            'invoices.view',
            'invoices.resend',
            'products.manage',
            'products.toggle_availability',
            'members.view',
            'loyalty.manage',
            'loyalty.view',
            'rfm.manage',
            'rfm.view',
            'promotions.manage',
            'promotions.view',
            'reports.sales.view',
            'reports.revenue.view',
            'users.manage_staff',
            'users.manage_all',
            'settings.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $roleMap = [
            'member' => [
                'member.portal',
            ],
            'kasir' => [
                'pos.access',
                'orders.view',
                'orders.create',
                'orders.edit',
                'orders.confirm_cash',
                'orders.cancel_unpaid',
                'orders.handover',
                'invoices.view',
                'invoices.resend',
                'products.toggle_availability',
            ],
            'kitchen' => [
                'kds.view',
                'kds.update_status',
                'orders.handover',
                'products.toggle_availability',
            ],
            'manager' => [
                'orders.view',
                'orders.cancel_unpaid',
                'orders.cancel_paid',
                'invoices.view',
                'invoices.resend',
                'products.manage',
                'products.toggle_availability',
                'members.view',
                'loyalty.manage',
                'loyalty.view',
                'rfm.manage',
                'rfm.view',
                'promotions.manage',
                'promotions.view',
                'reports.sales.view',
                'reports.revenue.view',
                'users.manage_staff',
                'settings.manage',
            ],
            'owner' => [
                'orders.view',
                'invoices.view',
                'members.view',
                'loyalty.view',
                'rfm.view',
                'promotions.view',
                'reports.sales.view',
                'reports.revenue.view',
                'users.manage_staff',
                'users.manage_all',
                'settings.manage',
            ],
        ];

        foreach ($roleMap as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($perms);
        }
    }
}
