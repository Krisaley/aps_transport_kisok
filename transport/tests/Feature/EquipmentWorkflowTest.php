<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\EquipmentModel;
use App\Models\Make;
use App\Models\Role;
use App\Models\User;
use App\Support\CurrentCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EquipmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(Company $company): User
    {
        $role = Role::create(['name' => 'Super-Admin', 'guard_name' => 'web', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function equipment(Company $company, string $stockNumber = 'APS-001'): Equipment
    {
        $make = Make::firstOrCreate(['name' => 'JLG']);
        $model = EquipmentModel::firstOrCreate(['make_id' => $make->id, 'name' => '450AJ']);

        return Equipment::create(['company_id' => $company->id, 'model_id' => $model->id, 'stock_number' => $stockNumber, 'serial_number' => 'SERIAL-'.$stockNumber]);
    }

    public function test_equipment_can_be_created_as_customer_owned_with_a_fleet_number(): void
    {
        $company = Company::create(['code' => 'APS', 'name' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        $user = $this->admin($company);
        $customer = Customer::create(['company_id' => $company->id, 'account_number' => 'C1', 'name' => 'Acme']);
        $make = Make::create(['name' => 'Hinowa']);
        $model = EquipmentModel::create(['make_id' => $make->id, 'name' => '20.10']);

        Livewire::actingAs($user)->test('pages::stock.equipment.form')
            ->set('model_id', $model->id)
            ->set('stock_number', 'APS-100')
            ->set('serial_number', 'SERIAL-100')
            ->set('fleet_number', 'ACME-42')
            ->set('owner_type', 'customer')
            ->set('customer_id', $customer->id)
            ->call('save')
            ->assertHasNoErrors();

        $equipment = Equipment::where('stock_number', 'APS-100')->firstOrFail();
        $this->assertSame('ACME-42', $equipment->fleet_number);
        $this->assertTrue($equipment->customers()->whereKey($customer->id)->exists());
    }

    public function test_owner_can_transfer_to_any_customer_or_tenant(): void
    {
        $company = Company::create(['code' => 'APS', 'name' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        $otherCompany = Company::create(['code' => 'OTHER', 'name' => 'Other', 'document_prefix' => 'OTHER', 'is_active' => true]);
        $user = $this->admin($company);
        $otherCustomer = Customer::create(['company_id' => $otherCompany->id, 'account_number' => 'C2', 'name' => 'Other Customer']);
        $equipment = $this->equipment($company);

        Livewire::actingAs($user)->test('pages::stock.equipment.index')
            ->set('transferEquipmentId', $equipment->id)
            ->set('ownerType', 'customer')
            ->set('ownerCustomerId', $otherCustomer->id)
            ->call('saveOwner')
            ->assertHasNoErrors();

        $this->assertSame($otherCompany->id, $equipment->fresh()->company_id);
        $this->assertTrue($equipment->customers()->whereKey($otherCustomer->id)->exists());

        $user->update(['company_id' => $otherCompany->id]);
        session([CurrentCompany::SESSION_KEY => $otherCompany->id]);
        Livewire::actingAs($user)->test('pages::stock.equipment.index')
            ->set('transferEquipmentId', $equipment->id)
            ->set('ownerType', 'tenant')
            ->set('ownerCompanyId', $company->id)
            ->call('saveOwner')
            ->assertHasNoErrors();

        $this->assertSame($company->id, $equipment->fresh()->company_id);
        $this->assertFalse($equipment->fresh()->customers()->exists());
    }

    public function test_index_searches_make_model_and_customer_and_shows_expected_actions(): void
    {
        $company = Company::create(['code' => 'APS', 'name' => 'APS Sales Stock', 'document_prefix' => 'APS', 'is_active' => true]);
        $user = $this->admin($company);
        $customer = Customer::create(['company_id' => $company->id, 'account_number' => 'C1', 'name' => 'Acme Hire']);
        $equipment = $this->equipment($company);
        $equipment->customers()->attach($customer);

        Livewire::actingAs($user)->test('pages::stock.equipment.index')
            ->set('search', 'jlg 450aj')
            ->assertSee($equipment->stock_number)
            ->set('search', 'ACME')
            ->assertSee($equipment->stock_number)
            ->assertSee('Transfer owner')
            ->assertSee('Raise movement');
    }

    public function test_raise_movement_prefills_equipment_and_customer_owner(): void
    {
        $company = Company::create(['code' => 'APS', 'name' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        $user = $this->admin($company);
        $customer = Customer::create(['company_id' => $company->id, 'account_number' => 'C1', 'name' => 'Acme']);
        $equipment = $this->equipment($company);
        $equipment->customers()->attach($customer);

        Livewire::withQueryParams(['equipment' => $equipment->id])
            ->actingAs($user)
            ->test('pages::operations.movements.form')
            ->assertSet('customer_id', $customer->id)
            ->assertSet('items.0.equipment_id', $equipment->id)
            ->assertSet('items.0.stock_number', $equipment->stock_number);
    }
}
