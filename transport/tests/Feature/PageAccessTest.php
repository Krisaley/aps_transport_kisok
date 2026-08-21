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

    public function test_setup_pages_require_the_matching_setup_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('setup.dashboard'))
            ->assertForbidden()
            ->assertSee('403 - Access denied');

        $this->get(route('setup.makes.index'))
            ->assertForbidden()
            ->assertSee('403 - Access denied');

        $permissions = [
            'setup.make.create' => 'setup.makes.index',
            'setup.model.create' => 'setup.models.index',
            'setup.customer.create' => 'setup.customers.index',
            'setup.site.create' => 'setup.sites.index',
            'setup.equipment.create' => 'setup.equipment.index',
            'setup.vehicle.create' => 'setup.vehicles.index',
        ];

        $user->givePermissionTo(array_keys($permissions));

        $this->actingAs($user)->get(route('setup.dashboard'))->assertOk();

        foreach ($permissions as $routeName) {
            $this->get(route($routeName))->assertOk();
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

        $user->givePermissionTo('user.movement.create');

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
            'setup.dashboard',
            'setup.makes.index',
            'setup.models.index',
            'setup.customers.index',
            'setup.sites.index',
            'setup.equipment.index',
            'setup.vehicles.index',
            'setup.makes.create',
            'setup.models.create',
            'setup.customers.create',
            'setup.sites.create',
            'setup.equipment.create',
            'setup.vehicles.create',
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
