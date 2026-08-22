<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // admin permissions
            'admin.user.create',
            'admin.user.update',
            'admin.user.delete',

            'admin.role.create',
            'admin.role.update',
            'admin.role.delete',

            'admin.conf.update',
            'admin.company.view',
            'admin.company.create',
            'admin.company.update',
            'admin.company.delete',

            'admin.logs.browse',

            // operations permissions
            'operations.movement.create',
            'operations.movement.update',
            'operations.movement.delete',
            'operations.movement.schedule',
            'operations.movement.assign',
            'operations.movement.complete',
            'operations.movement.amend_completed',
            'user.document.issue',

            // crm permissions
            'crm.customer.create',
            'crm.customer.update',
            'crm.customer.delete',

            'crm.site.create',
            'crm.site.update',
            'crm.site.delete',

            // stock permissions
            'stock.equipment.create',
            'stock.equipment.update',
            'stock.equipment.delete',

            'stock.make-model.create',
            'stock.make-model.update',
            'stock.make-model.delete',

            // transport permissions

            'transport.vehicle.create',
            'transport.vehicle.update',
            'transport.vehicle.delete',

            // pwa permissions
            'pwa.driver',
            'pwa.yard_receipt',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate([
                'name' => $permission,
            ]);
        }
    }
}
