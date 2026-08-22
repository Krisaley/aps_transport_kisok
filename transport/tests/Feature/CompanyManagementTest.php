<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Role;
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
            ->call('save')
            ->assertHasNoErrors();

        $company = Company::where('code', 'APS')->firstOrFail();

        Livewire::actingAs($admin)->test('pages::settings.companies.form', ['company' => $company])
            ->set('name', 'APS Ltd')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('APS Ltd', $company->fresh()->name);
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

    public function test_company_with_tenant_data_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $company = Company::create(['name' => 'APS', 'code' => 'APS', 'document_prefix' => 'APS', 'is_active' => true]);
        Customer::create(['company_id' => $company->id, 'account_number' => 'C1', 'name' => 'Customer']);

        Livewire::actingAs($admin)->test('pages::settings.companies.index')
            ->set('deleteCompanyId', $company->id)
            ->call('deleteCompany')
            ->assertHasErrors('deleteCompany');

        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }
}
