<?php

namespace Tests\Feature\Setup;

use App\Models\EquipmentModel;
use App\Models\Make;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MakesIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name' => 'Super-Admin',
            'guard_name' => 'web',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole($role);
    }

    public function test_multiple_makes_can_be_expanded_without_losing_manual_state_during_search(): void
    {
        $mercedes = Make::create(['name' => 'Mercedes-Benz']);
        $palfinger = Make::create(['name' => 'Palfinger']);
        $canycom = Make::create(['name' => 'Canycom']);

        EquipmentModel::create(['make_id' => $mercedes->id, 'name' => 'Sprinter 315']);
        EquipmentModel::create(['make_id' => $palfinger->id, 'name' => 'PT28T']);
        EquipmentModel::create(['make_id' => $canycom->id, 'name' => 'S25A']);

        Livewire::actingAs($this->user)
            ->test('pages::stock.makes.index')
            ->call('toggleMake', $mercedes->id)
            ->call('toggleMake', $canycom->id)
            ->assertSet('expandedMakes', [$mercedes->id, $canycom->id])
            ->assertSee('Sprinter 315')
            ->assertSee('S25A')
            ->set('search', 'PT28')
            ->assertSet('expandedMakes', [$mercedes->id, $canycom->id])
            ->assertSee('Palfinger')
            ->assertSee('PT28T')
            ->assertDontSee('Mercedes-Benz')
            ->assertDontSee('Canycom')
            ->set('search', '')
            ->assertSet('expandedMakes', [$mercedes->id, $canycom->id])
            ->assertSee('Sprinter 315')
            ->assertSee('S25A');
    }

    public function test_model_search_filters_visible_models_and_make_search_shows_all_models(): void
    {
        $palfinger = Make::create(['name' => 'Palfinger']);
        EquipmentModel::create(['make_id' => $palfinger->id, 'name' => 'P200A']);
        EquipmentModel::create(['make_id' => $palfinger->id, 'name' => 'PT28T']);

        $component = Livewire::actingAs($this->user)
            ->test('pages::stock.makes.index')
            ->set('search', 'PT28')
            ->assertSee('Palfinger')
            ->assertSee('PT28T')
            ->assertSee('1 of 2')
            ->assertDontSee('P200A');

        $component
            ->set('search', '  Palfinger  ')
            ->assertSee('P200A')
            ->assertSee('PT28T')
            ->assertDontSee('1 of 2');
    }

    public function test_sorting_is_whitelisted_and_toggles_name_direction(): void
    {
        Make::create(['name' => 'Alpha']);
        Make::create(['name' => 'Zulu']);

        Livewire::actingAs($this->user)
            ->test('pages::stock.makes.index')
            ->assertSeeInOrder(['Alpha', 'Zulu'])
            ->call('sort', 'name')
            ->assertSet('sortDirection', 'DESC')
            ->assertSeeInOrder(['Zulu', 'Alpha'])
            ->call('sort', 'created_at')
            ->assertSet('sortBy', 'name')
            ->assertSet('sortDirection', 'DESC');
    }
}
