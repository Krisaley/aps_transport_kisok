<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConfigurationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_configuration_uses_general_and_services_tabs_with_provider_specific_options(): void
    {
        $role = Role::create(['name' => 'Super-Admin', 'guard_name' => 'web', 'is_active' => true]);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        Livewire::actingAs($user)->test('pages::settings.config.index')
            ->assertSee('General settings')
            ->assertSee('Services')
            ->assertSee('Site name')
            ->assertDontSee('Google Maps API key')
            ->set('configurationTab', 'services')
            ->assertSee('Address validation service')
            ->assertSee('Postcodes.io requires no API key')
            ->assertDontSee('Google Maps API key')
            ->set('postcodeValidationProvider', 'google')
            ->assertSee('Google Maps API key')
            ->assertSee('Default country code')
            ->set('postcodeValidationProvider', 'manual')
            ->assertSee('will not be checked by an external service')
            ->assertDontSee('Google Maps API key');
    }
}
