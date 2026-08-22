<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_pages_require_the_matching_permission_family(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);

        $permissions = [
            'stock.make-model.create' => 'stock.makes.index',
            'crm.customer.create' => 'crm.customers.index',
            'crm.site.create' => 'crm.sites.index',
            'stock.equipment.create' => 'stock.equipment.index',
            'transport.vehicle.create' => 'transport.vehicles.index',
        ];

        foreach ($permissions as $permission => $routeName) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertForbidden()
                ->assertSee('403 - Access denied');

            $user->givePermissionTo($permission);
            $user->unsetRelation('permissions');
            $this->actingAs($user)->get(route($routeName))->assertOk();
            $user->revokePermissionTo($permission);
            $user->unsetRelation('permissions');
        }
    }

    public function test_movement_page_requires_a_movement_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('operations.movements.index'))
            ->assertForbidden()
            ->assertSee('403 - Access denied');

        $user->givePermissionTo('operations.movement.create');

        $this->actingAs($user)->get(route('operations.movements.index'))->assertOk();
    }

    public function test_super_admin_bypasses_all_application_area_gates(): void
    {
        $role = Role::create([
            'name' => 'Super-Admin',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $routes = [
            'settings.dashboard',
            'settings.users.index',
            'settings.roles.index',
            'stock.makes.index',
            'crm.customers.index',
            'crm.sites.index',
            'stock.equipment.index',
            'transport.vehicles.index',
            'stock.makes.create',
            'crm.customers.create',
            'crm.sites.create',
            'stock.equipment.create',
            'transport.vehicles.create',
            'operations.movements.index',
            'operations.movements.create',
        ];

        foreach ($routes as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }

        $this->get(route('settings.users.index'))->assertSee('Add User');
        $this->get(route('settings.roles.index'))->assertSee('Add Role');
    }
}
