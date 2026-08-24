<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name' => 'Super-Admin', 'guard_name' => 'web', 'is_active' => true]);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_company_can_be_created_and_updated(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test('pages::settings.companies.form')
            ->set('name', 'Access Platform Services')
            ->set('code', 'APS')
            ->set('document_prefix', 'APS')
            ->set('headOffice', ['site_name' => 'Head Office', 'site_address_line_1' => '1 Main Street', 'site_address_line_2' => null, 'site_town' => 'Peterborough', 'site_county' => null, 'site_postcode' => 'PE1 1AA'])
            ->call('save')
            ->assertHasNoErrors();

        $company = Company::where('code', 'APS')->firstOrFail();
        $this->assertSame('Head Office', $company->homeSite->name);

        Livewire::actingAs($admin)->test('pages::settings.companies.form', ['company' => $company])
            ->set('name', 'APS Ltd')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('APS Ltd', $company->fresh()->name);
    }

    public function test_new_company_is_active_by_default_and_next_advances(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test('pages::settings.companies.form')
            ->assertSet('is_active', true)
            ->set('name', 'Access Platform Sales')
            ->set('code', 'APS')
            ->call('next')
            ->assertHasNoErrors()
            ->assertSet('step', 2);
    }

    public function test_company_details_can_be_updated_when_hidden_branding_is_incomplete(): void
    {
        $admin = $this->admin();
        $company = Company::create([
            'name' => 'APS',
            'code' => 'APS',
            'document_prefix' => '',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)->test('pages::settings.companies.form', ['company' => $company])
            ->set('name', 'Access Platform Sales')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Access Platform Sales', $company->fresh()->name);
    }

    public function test_livewire_update_url_uses_forwarded_https_scheme(): void
    {
        $admin = $this->admin();
        $company = Company::create([
            'name' => 'APS',
            'code' => 'APS',
            'document_prefix' => 'APS',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
            ->withHeaders([
                'X-Forwarded-Host' => 'transport.krisaley.co.uk',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get("/settings/companies/{$company->id}/update");

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/data-update-uri="https:\/\/transport\.krisaley\.co\.uk\/livewire-[^"]+\/update"/',
            $response->getContent(),
        );
    }

    public function test_user_default_company_must_be_an_assigned_company(): void
    {
        $admin = $this->admin();
        $first = Company::create(['name' => 'APS', 'code' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        $second = Company::create(['name' => 'APSR', 'code' => 'APSR', 'document_prefix' => 'APSR', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $first->id, 'is_active' => true]);
        $user->companies()->sync([$first->id]);

        Livewire::actingAs($admin)->test('pages::settings.users.update', ['user' => $user])
            ->set('selectedCompanies', [$first->id, $second->id])
            ->set('defaultCompanyId', $second->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($second->id, $user->fresh()->company_id);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $user->companies()->pluck('companies.id')->all());
    }

    public function test_company_with_tenant_data_is_soft_deleted(): void
    {
        $admin = $this->admin();
        $company = Company::create(['name' => 'APS', 'code' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        Company::create(['name' => 'APSR', 'code' => 'APSR', 'document_prefix' => 'APSR', 'is_active' => true]);
        Customer::create(['company_id' => $company->id, 'account_number' => 'C1', 'name' => 'Customer']);

        Livewire::actingAs($admin)->test('pages::settings.companies.index')
            ->set('deleteCompanyId', $company->id)
            ->call('deleteCompany')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
        $this->assertDatabaseHas('customers', ['company_id' => $company->id]);
    }

    public function test_saved_depot_can_be_removed_without_deleting_the_shared_site(): void
    {
        $admin = $this->admin();
        $company = Company::create(['name' => 'APS', 'code' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        $headOffice = Site::create(['name' => 'Head Office', 'address_line_1' => '1 Main Street', 'postcode' => 'PE1 1AA']);
        $depot = Site::create(['name' => 'Depot', 'address_line_1' => '2 Main Street', 'postcode' => 'PE1 1AB']);
        $company->sites()->attach([$headOffice->id, $depot->id]);
        $company->update(['home_site_id' => $headOffice->id]);

        Livewire::actingAs($admin)->test('pages::settings.companies.form', ['company' => $company])
            ->call('detachDepot', $depot->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('company_site', ['company_id' => $company->id, 'site_id' => $depot->id]);
        $this->assertDatabaseHas('sites', ['id' => $depot->id]);
    }
}
