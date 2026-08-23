<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\EquipmentModel;
use App\Models\Make;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_wizard_saves_shared_sites_and_tenant_equipment(): void
    {
        $company = Company::create(['code' => 'APS', 'name' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        $role = Role::create(['name' => 'Super-Admin', 'guard_name' => 'web', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $user->assignRole($role);
        $head = Site::create(['name' => 'Head Office', 'address_line_1' => 'One Road', 'postcode' => 'PE1']);
        $depot = Site::create(['name' => 'Depot', 'address_line_1' => 'Two Road', 'postcode' => 'PE2']);
        $make = Make::create(['name' => 'Hinowa']);
        $model = EquipmentModel::create(['make_id' => $make->id, 'name' => '20.10']);
        $equipment = Equipment::create(['company_id' => $company->id, 'model_id' => $model->id, 'stock_number' => '240/1', 'serial_number' => 'ABC']);

        Livewire::actingAs($user)->test('pages::crm.customers.create')->set('name', 'Customer Ltd')->set('trading_name', 'Customer Trading')->set('account_number', 'C100')->set('home_site_id', $head->id)->set('siteIds', [$depot->id])->set('equipmentIds', [$equipment->id])->call('save')->assertHasNoErrors();

        $customer = Customer::where('account_number', 'C100')->firstOrFail();
        $this->assertSame($head->id, $customer->home_site_id);
        $this->assertEqualsCanonicalizing([$head->id, $depot->id], $customer->sites()->pluck('sites.id')->all());
        $this->assertSame([$equipment->id], $customer->equipment()->pluck('equipment.id')->all());
    }

    public function test_customer_update_page_loads_all_management_tabs(): void
    {
        $company = Company::create(['code' => 'APS', 'name' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        $role = Role::create(['name' => 'Super-Admin', 'guard_name' => 'web', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $user->assignRole($role);
        $customer = Customer::create(['company_id' => $company->id, 'name' => 'Customer', 'account_number' => 'C1']);

        Livewire::actingAs($user)->test('pages::crm.customers.update', ['customer' => $customer])
            ->assertOk()
            ->assertSee('Basic Details')
            ->assertSee('Addresses')
            ->assertSee('Equipment')
            ->assertSee('Movements');
    }
}
