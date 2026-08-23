<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentModel;
use App\Models\Make;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CrossTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function tenantAdmin(Company $company): User
    {
        $role = Role::create(['name' => 'Tenant-Admin', 'guard_name' => 'web', 'is_active' => true]);
        $permissions = collect(['admin.company.view', 'admin.company.update', 'crm.site.update', 'stock.equipment.update'])
            ->map(fn (string $name) => Permission::create(['name' => $name, 'guard_name' => 'web']));
        $role->givePermissionTo($permissions);
        $user = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_equipment_is_tenant_scoped_while_sites_are_app_wide(): void
    {
        $first = Company::create(['code' => 'ONE', 'name' => 'One', 'document_prefix' => 'ONE', 'is_active' => true]);
        $second = Company::create(['code' => 'TWO', 'name' => 'Two', 'document_prefix' => 'TWO', 'is_active' => true]);
        $user = $this->tenantAdmin($first);
        $make = Make::create(['name' => 'Hinowa']);
        $model = EquipmentModel::create(['make_id' => $make->id, 'name' => '20.10']);
        Equipment::create(['company_id' => $first->id, 'model_id' => $model->id, 'stock_number' => 'ONE-001', 'serial_number' => 'ONE-SERIAL']);
        Equipment::create(['company_id' => $second->id, 'model_id' => $model->id, 'stock_number' => 'TWO-001', 'serial_number' => 'TWO-SERIAL']);
        Site::create(['company_id' => $first->id, 'name' => 'One depot', 'address_line_1' => 'One Road', 'postcode' => 'PE1']);
        Site::create(['company_id' => $second->id, 'name' => 'Two depot', 'address_line_1' => 'Two Road', 'postcode' => 'PE2']);

        Livewire::actingAs($user)->test('pages::stock.equipment.index')->assertSee('ONE-001')->assertDontSee('TWO-001');
        Livewire::actingAs($user)->test('pages::crm.sites.index')->assertSee('One depot')->assertSee('Two depot');
    }

    public function test_cross_tenant_equipment_and_company_editing_returns_not_found_while_sites_remain_shared(): void
    {
        $first = Company::create(['code' => 'ONE', 'name' => 'One', 'document_prefix' => 'ONE', 'is_active' => true]);
        $second = Company::create(['code' => 'TWO', 'name' => 'Two', 'document_prefix' => 'TWO', 'is_active' => true]);
        $user = $this->tenantAdmin($first);
        $make = Make::create(['name' => 'Hinowa']);
        $model = EquipmentModel::create(['make_id' => $make->id, 'name' => '20.10']);
        $equipment = Equipment::create(['company_id' => $second->id, 'model_id' => $model->id, 'stock_number' => 'TWO-001', 'serial_number' => 'TWO-SERIAL']);
        $site = Site::create(['company_id' => $second->id, 'name' => 'Two depot', 'address_line_1' => 'Two Road', 'postcode' => 'PE2']);

        Livewire::actingAs($user)->test('pages::stock.equipment.form', ['equipment' => $equipment])->assertNotFound();
        Livewire::actingAs($user)->test('pages::crm.sites.form', ['site' => $site])->assertOk();
        Livewire::actingAs($user)->test('pages::settings.companies.form', ['company' => $second])->assertNotFound();
    }
}
