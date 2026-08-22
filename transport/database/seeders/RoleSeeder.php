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
            'crm.customer.create',
            'crm.customer.update',
            'crm.customer.delete',
            'crm.site.create',
            'crm.site.update',
            'crm.site.delete',
            'stock.equipment.create',
            'stock.equipment.update',
            'stock.equipment.delete',
            'transport.vehicle.create',
            'transport.vehicle.update',
            'transport.vehicle.delete',
            'stock.make-model.create',
            'stock.make-model.update',
            'stock.make-model.delete',
            // user permissions
            'operations.movement.create',
            'operations.movement.update',
            'operations.movement.delete',
            'operations.movement.schedule',
            'operations.movement.assign',
            'operations.movement.complete',
            'operations.movement.amend_completed',
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
            'crm.customer.create',
            'crm.customer.update',
            'crm.site.create',
            'crm.site.update',
            'stock.equipment.create',
            'stock.equipment.update',
            'transport.vehicle.create',
            'transport.vehicle.update',
            'stock.make-model.create',
            'stock.make-model.update',
            // user permissions
            'operations.movement.create',
            'operations.movement.update',
            'operations.movement.schedule',
            'operations.movement.assign',
            'operations.movement.complete',
            'operations.movement.amend_completed',
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
            'crm.customer.create',
            'crm.customer.update',
            'crm.site.create',
            'crm.site.update',
            'stock.equipment.create',
            'stock.equipment.update',
            'transport.vehicle.create',
            'transport.vehicle.update',
            'stock.make-model.create',
            'stock.make-model.update',
            // user permissions
            'operations.movement.create',
            'operations.movement.update',
            'operations.movement.schedule',
            'operations.movement.assign',
            'operations.movement.complete',
            'operations.movement.amend_completed',
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
            'crm.customer.create',
            'crm.customer.update',
            'crm.site.create',
            'crm.site.update',
            'stock.equipment.create',
            'stock.equipment.update',
            'transport.vehicle.create',
            'transport.vehicle.update',
            'stock.make-model.create',
            'stock.make-model.update',
            // user permissions
            'operations.movement.create',
            'operations.movement.update',
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
            'crm.site.update',
            // user permissions
            // pwa permissions
            'pwa.driver',
        ]);

        $scheduler = Role::updateOrCreate(['name' => 'Scheduler']);
        $scheduler->syncPermissions([
            'crm.customer.create', 'crm.customer.update', 'crm.site.create', 'crm.site.update',
            'stock.equipment.create', 'stock.equipment.update',
            'operations.movement.create', 'operations.movement.update', 'operations.movement.schedule',
            'operations.movement.assign', 'user.document.issue', 'pwa.yard_receipt',
        ]);

        $clerical = Role::updateOrCreate(['name' => 'Clerical-Admin']);
        $clerical->syncPermissions([
            'crm.customer.create', 'crm.customer.update', 'crm.site.create', 'crm.site.update',
            'stock.equipment.create', 'stock.equipment.update',
            'operations.movement.create', 'operations.movement.update', 'user.document.issue', 'pwa.yard_receipt',
        ]);

        $systemAdmin = Role::updateOrCreate(['name' => 'System-Admin']);
        $systemAdmin->syncPermissions(Permission::all());

        $yard = Role::updateOrCreate(['name' => 'Yard']);
        $yard->syncPermissions(['pwa.yard_receipt']);

    }
}
