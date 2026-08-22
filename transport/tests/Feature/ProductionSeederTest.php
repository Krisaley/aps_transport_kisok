<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeding_does_not_overwrite_tenants_or_users(): void
    {
        $company = Company::create([
            'code' => 'APS',
            'name' => 'Production tenant name',
            'address' => 'Production address',
            'document_prefix' => 'LIVE',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Production administrator',
            'password' => Hash::make('production-password'),
            'is_active' => true,
        ]);
        $password = $user->password;
        $customPermission = Permission::create(['name' => 'custom.production.permission', 'guard_name' => 'web']);
        $scheduler = Role::create(['name' => 'Scheduler', 'guard_name' => 'web', 'is_active' => true]);
        $scheduler->givePermissionTo($customPermission);

        $this->seed(ProductionSeeder::class);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Production tenant name',
            'address' => 'Production address',
            'document_prefix' => 'LIVE',
        ]);
        $this->assertSame('Production administrator', $user->fresh()->name);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertTrue($scheduler->fresh()->hasPermissionTo($customPermission));
    }
}
