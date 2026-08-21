<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $super_admin = Role::updateOrCreate([
            'name' => 'Super-Admin',
        ]);

        $global_admin = Role::updateOrCreate([
            'name' => 'Global-Admin',
        ]);
        $global_admin->givePermissionTo([
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
        ]);

        $transport_manager = Role::updateOrCreate([
            'name' => 'Transport-Manager',
        ]);
        $transport_manager->givePermissionTo([
            // admin permissions
            // setup permissions
            'setup.customer.create',
            'setup.customer.update',
            'setup.site.create',
            'setup.site.update',
            'setup.equipment.create',
            'setup.equipment.update',
            'setup.vehicle.create',
            'setup.vehicle.update',
            'setup.make.create',
            'setup.make.update',
            'setup.model.create',
            'setup.model.update',
            'setup.logs.browse',
            // user permissions
            'user.movement.create',
            'user.movement.update',
            'user.movement.schedule',
            'user.movement.assign',
            'user.movement.complete',
            'user.movement.amend_completed',
            'user.document.issue',
            // pwa permissions
            'pwa.driver',
            'pwa.yard_receipt',
        ]);

        $ops_manager = Role::updateOrCreate([
            'name' => 'Operations-Manager',
        ]);
        $ops_manager->givePermissionTo([
            // admin permissions
            'admin.user.create',
            'admin.user.update',
            'admin.role.create',
            'admin.role.update',
            'admin.conf.update',
            'admin.company.update',
            'admin.logs.browse',
            // setup permissions
            'setup.customer.create',
            'setup.customer.update',
            'setup.site.create',
            'setup.site.update',
            'setup.equipment.create',
            'setup.equipment.update',
            'setup.vehicle.create',
            'setup.vehicle.update',
            'setup.make.create',
            'setup.make.update',
            'setup.model.create',
            'setup.model.update',
            'setup.logs.browse',
            // user permissions
            'user.movement.create',
            'user.movement.update',
            'user.movement.schedule',
            'user.movement.assign',
            'user.movement.complete',
            'user.movement.amend_completed',
            'user.document.issue',
            // pwa permissions
            'pwa.driver',
            'pwa.yard_receipt',
        ]);

        $sales_clerk = Role::updateOrCreate([
            'name' => 'Sales-Administrator',
        ]);
        $sales_clerk->givePermissionTo([
            // admin permissions
            // setup permissions
            'setup.customer.create',
            'setup.customer.update',
            'setup.site.create',
            'setup.site.update',
            'setup.equipment.create',
            'setup.equipment.update',
            'setup.vehicle.create',
            'setup.vehicle.update',
            'setup.make.create',
            'setup.make.update',
            'setup.model.create',
            'setup.model.update',
            'setup.logs.browse',
            // user permissions
            'user.movement.create',
            'user.movement.update',
            'user.document.issue',
            // pwa permissions
            'pwa.driver',
            'pwa.yard_receipt',
        ]);

        $driver = Role::updateOrCreate([
            'name' => 'Driver',
        ]);
        $driver->givePermissionTo([
            // admin permissions
            // setup permissions
            'setup.site.update',
            // user permissions
            // pwa permissions
            'pwa.driver',
        ]);

        $scheduler = Role::updateOrCreate(['name' => 'Scheduler']);
        $scheduler->syncPermissions([
            'setup.customer.create', 'setup.customer.update', 'setup.site.create', 'setup.site.update',
            'setup.equipment.create', 'setup.equipment.update',
            'user.movement.create', 'user.movement.update', 'user.movement.schedule',
            'user.movement.assign', 'user.document.issue', 'pwa.yard_receipt',
        ]);

        $clerical = Role::updateOrCreate(['name' => 'Clerical-Admin']);
        $clerical->syncPermissions([
            'setup.customer.create', 'setup.customer.update', 'setup.site.create', 'setup.site.update',
            'setup.equipment.create', 'setup.equipment.update',
            'user.movement.create', 'user.movement.update', 'user.document.issue', 'pwa.yard_receipt',
        ]);

        $systemAdmin = Role::updateOrCreate(['name' => 'System-Admin']);
        $systemAdmin->syncPermissions(Permission::all());

        $yard = Role::updateOrCreate(['name' => 'Yard']);
        $yard->syncPermissions(['pwa.yard_receipt']);

    }
}
