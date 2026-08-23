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
use Tests\TestCase;

class InlineCreateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(Company $company): User
    {
        $role = Role::create(['name' => 'Super-Admin', 'guard_name' => 'web', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_equipment_form_can_create_and_select_a_model(): void
    {
        $company = Company::create(['code' => 'APS', 'name' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        $user = $this->admin($company);
        $make = Make::create(['name' => 'JLG']);
        Livewire::actingAs($user)->test('pages::stock.equipment.form')->set('new_make_id', $make->id)->set('new_model_name', '450AJ')->call('createModel')->assertHasNoErrors()->assertSet('model_id', fn ($id) => EquipmentModel::whereKey($id)->exists());
    }

    public function test_equipment_is_created_for_the_active_company(): void
    {
        $company = Company::create(['code' => 'APS', 'name' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        $user = $this->admin($company);
        $make = Make::create(['name' => 'Hinowa']);
        $model = EquipmentModel::create(['make_id' => $make->id, 'name' => '20.10']);

        Livewire::actingAs($user)->test('pages::stock.equipment.form')
            ->set('model_id', $model->id)
            ->set('stock_number', '240/0001')
            ->set('serial_number', 'ABC123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Equipment::where('company_id', $company->id)->where('stock_number', '240/0001')->exists());
    }

    public function test_company_form_can_create_and_select_a_home_site(): void
    {
        $company = Company::create(['code' => 'APS', 'name' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        $user = $this->admin($company);
        Livewire::actingAs($user)->test('pages::settings.companies.form', ['company' => $company])->set('addressType', 'head')->set('site_name', 'Depot')->set('site_address_line_1', 'Depot Road')->set('site_postcode', 'PE28 5YQ')->call('saveAddress')->assertHasNoErrors();

        $this->assertTrue(Site::whereKey($company->fresh()->home_site_id)->exists());
    }

    public function test_changing_a_company_head_office_starts_with_shared_site_search(): void
    {
        $company = Company::create(['code' => 'APS', 'name' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        $user = $this->admin($company);
        $site = Site::create(['name' => 'Head Office', 'address_line_1' => 'Depot Road', 'postcode' => 'PE28 5YQ']);
        $company->sites()->attach($site);
        $company->update(['home_site_id' => $site->id]);

        Livewire::actingAs($user)->test('pages::settings.companies.form', ['company' => $company])
            ->call('address', 'head', null, $site->id)
            ->assertSet('creatingAddress', false)
            ->assertSet('selectedSiteId', null)
            ->assertSee('Find an existing address')
            ->assertDontSee('Address line 1');
    }
}
