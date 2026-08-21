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
            'admin.company.update',

            'admin.logs.browse',

            // setup permissions
            'setup.customer.create',
            'setup.customer.update',
            'setup.customer.delete',
            
            'setup.site.create',
            'setup.site.update',
            'setup.site.delete',
            
            'setup.equipment.create',
            'setup.equipment.update',
            'setup.equipment.delete',
            
            'setup.vehicle.create',
            'setup.vehicle.update',
            'setup.vehicle.delete',
            
            'setup.make.create',
            'setup.make.update',
            'setup.make.delete',
            
            'setup.model.create',
            'setup.model.update',
            'setup.model.delete',

            'setup.logs.browse',

            // user permissions
            'user.movement.create',
            'user.movement.update',
            'user.movement.delete',
            'user.movement.schedule',
            'user.movement.assign',
            'user.movement.complete',
            'user.movement.amend_completed',
            'user.document.issue',

            // pwa permissions
            'pwa.driver',
            'pwa.yard_receipt',
        ];

        foreach ($permissions as $permission)
            {
                Permission::updateOrCreate([
                    'name' => $permission,
                ]);
            }
    }
}
