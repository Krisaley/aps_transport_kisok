<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Movement;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MovementFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_to_site_chain_saves_collection_and_delivery_for_each_machine(): void
    {
        $company = Company::create(['code' => 'APS', 'name' => 'APS', 'is_active' => true]);
        $role = Role::create(['name' => 'Super-Admin', 'guard_name' => 'web', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $user->assignRole($role);
        $customer = Customer::create(['company_id' => $company->id, 'account_number' => 'C1', 'name' => 'Customer']);
        $depot = Site::create(['company_id' => $company->id, 'name' => 'Depot', 'address_line_1' => 'Depot Road', 'postcode' => 'PE1', 'address_code' => 'DEPOT']);
        $siteOne = Site::create(['company_id' => $company->id, 'name' => 'Site 1', 'address_line_1' => 'One Road', 'postcode' => 'PE2', 'address_code' => 'SITE1']);
        $siteTwo = Site::create(['company_id' => $company->id, 'name' => 'Site 2', 'address_line_1' => 'Two Road', 'postcode' => 'PE3', 'address_code' => 'SITE2']);

        $action = fn (string $type, Site $site) => ['id' => null, 'action_type' => $type, 'site_id' => $site->id, 'driver_id' => null, 'vehicle_id' => null, 'schedule_start' => null, 'schedule_end' => null, 'notes' => null];
        $item = fn (string $description, int $collection, int $delivery) => ['id' => null, 'collection_action_index' => $collection, 'delivery_action_index' => $delivery, 'stock_number' => null, 'serial_number' => null, 'description' => $description, 'accessories' => null];

        Livewire::actingAs($user)
            ->test('pages::operations.movements.form')
            ->set('company_id', $company->id)
            ->set('customer_id', $customer->id)
            ->set('reference', 'MOV-CHAIN')
            ->set('movement_type', 'site_to_site')
            ->set('actions', [
                $action('collection', $depot),
                $action('delivery', $siteOne),
                $action('collection', $siteOne),
                $action('delivery', $siteTwo),
                $action('collection', $siteTwo),
                $action('delivery', $depot),
            ])
            ->set('items', [
                $item('Machine 1', 0, 1),
                $item('Machine 2', 2, 3),
                $item('Machine 3', 4, 5),
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('operations.movements.index'));

        $movement = Movement::where('reference', 'MOV-CHAIN')->with(['actions', 'items'])->firstOrFail();
        $this->assertSame('site_to_site', $movement->movement_type);
        $this->assertSame($depot->id, $movement->collection_site_id);
        $this->assertSame($depot->id, $movement->delivery_site_id);
        $this->assertCount(6, $movement->actions);
        $this->assertSame([1, 2, 3, 4, 5, 6], $movement->actions->pluck('sequence')->all());
        $this->assertSame(
            [[1, 2], [3, 4], [5, 6]],
            $movement->items->map(fn ($movementItem) => [$movementItem->collectionAction->sequence, $movementItem->deliveryAction->sequence])->all(),
        );

        $html = view('documents.movement', [
            'movement' => $movement->load(['company', 'customer', 'actions.site', 'actions.driver', 'actions.vehicle', 'items.accessories', 'items.collectionAction.site', 'items.deliveryAction.site', 'photos']),
            'type' => 'driver_manifest',
        ])->render();
        $this->assertStringContainsString('Equipment Delivery &amp; Collection', $html);
        $this->assertStringContainsString('Delivery Lines', $html);
        $this->assertStringContainsString('Collection Lines', $html);
        $this->assertStringContainsString('Site 1', $html);
        $this->assertStringContainsString('Site 2', $html);
    }
}
