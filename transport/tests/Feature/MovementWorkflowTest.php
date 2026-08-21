<?php

namespace Tests\Feature;

use App\Enums\MovementActionType;
use App\Enums\MovementStatus;
use App\Models\Customer;
use App\Models\Movement;
use App\Models\Site;
use App\Models\User;
use App\Services\MovementWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MovementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function movement(User $user, Customer $customer, Site $site): Movement
    {
        return Movement::create(['status' => 'awaiting_schedule', 'movement_type' => 'delivery', 'reference' => uniqid('MOV-'), 'customer_id' => $customer->id, 'delivery_site_id' => $site->id, 'collection_site_id' => $site->id, 'created_by' => $user->id, 'updated_by' => $user->id]);
    }

    public function test_scheduling_requires_complete_actions(): void
    {
        $u = User::factory()->create();
        $c = Customer::create(['account_number' => 'C1', 'name' => 'Customer']);
        $s = Site::create(['name' => 'Yard', 'address_line_1' => 'Road', 'postcode' => 'PE1', 'address_code' => 'YARD']);
        $m = $this->movement($u, $c, $s);
        $this->expectException(ValidationException::class);
        app(MovementWorkflow::class)->transition($m, MovementStatus::Scheduled, $u);
    }

    public function test_overlapping_driver_allocation_is_rejected(): void
    {
        $u = User::factory()->create();
        $d = User::factory()->create();
        $c = Customer::create(['account_number' => 'C2', 'name' => 'Customer']);
        $s = Site::create(['name' => 'Yard', 'address_line_1' => 'Road', 'postcode' => 'PE1', 'address_code' => 'YARD2']);
        $first = $this->movement($u, $c, $s);
        $firstCollection = $first->actions()->create(['sequence' => 1, 'action_type' => MovementActionType::Collection, 'site_id' => $s->id, 'driver_id' => $d->id, 'schedule_start' => '2026-08-24 08:30', 'schedule_end' => '2026-08-24 09:00']);
        $firstDelivery = $first->actions()->create(['sequence' => 2, 'action_type' => MovementActionType::Delivery, 'site_id' => $s->id, 'driver_id' => $d->id, 'schedule_start' => '2026-08-24 09:00', 'schedule_end' => '2026-08-24 10:00']);
        $first->items()->create(['description' => 'Machine 1', 'movement_action' => 'delivery', 'movement_action_id' => $firstDelivery->id, 'collection_action_id' => $firstCollection->id, 'delivery_action_id' => $firstDelivery->id]);
        app(MovementWorkflow::class)->transition($first, MovementStatus::Scheduled, $u);
        $second = $this->movement($u, $c, $s);
        $secondCollection = $second->actions()->create(['sequence' => 1, 'action_type' => MovementActionType::Collection, 'site_id' => $s->id, 'driver_id' => $d->id, 'schedule_start' => '2026-08-24 09:30', 'schedule_end' => '2026-08-24 10:30']);
        $secondDelivery = $second->actions()->create(['sequence' => 2, 'action_type' => MovementActionType::Delivery, 'site_id' => $s->id, 'driver_id' => $d->id, 'schedule_start' => '2026-08-24 10:30', 'schedule_end' => '2026-08-24 11:00']);
        $second->items()->create(['description' => 'Machine 2', 'movement_action' => 'delivery', 'movement_action_id' => $secondDelivery->id, 'collection_action_id' => $secondCollection->id, 'delivery_action_id' => $secondDelivery->id]);
        $this->expectException(ValidationException::class);
        app(MovementWorkflow::class)->transition($second, MovementStatus::Scheduled, $u);
    }

    public function test_delivery_must_follow_collection(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['account_number' => 'C4', 'name' => 'Customer']);
        $site = Site::create(['name' => 'Yard', 'address_line_1' => 'Road', 'postcode' => 'PE1', 'address_code' => 'YARD4']);
        $movement = $this->movement($user, $customer, $site);
        $delivery = $movement->actions()->create(['sequence' => 1, 'action_type' => MovementActionType::Delivery, 'site_id' => $site->id, 'schedule_start' => '2026-08-24 09:00', 'schedule_end' => '2026-08-24 09:30']);
        $collection = $movement->actions()->create(['sequence' => 2, 'action_type' => MovementActionType::Collection, 'site_id' => $site->id, 'schedule_start' => '2026-08-24 09:30', 'schedule_end' => '2026-08-24 10:00']);
        $movement->items()->create(['description' => 'Machine', 'movement_action' => 'delivery', 'movement_action_id' => $delivery->id, 'collection_action_id' => $collection->id, 'delivery_action_id' => $delivery->id]);

        $this->expectException(ValidationException::class);
        app(MovementWorkflow::class)->transition($movement, MovementStatus::Scheduled, $user);
    }

    public function test_multi_stop_chain_tracks_each_machine_from_collection_to_delivery(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['account_number' => 'C5', 'name' => 'Customer']);
        $depot = Site::create(['name' => 'Depot', 'address_line_1' => 'Depot Road', 'postcode' => 'PE1', 'address_code' => 'DEPOT']);
        $siteOne = Site::create(['name' => 'Site 1', 'address_line_1' => 'One Road', 'postcode' => 'PE2', 'address_code' => 'SITE1']);
        $siteTwo = Site::create(['name' => 'Site 2', 'address_line_1' => 'Two Road', 'postcode' => 'PE3', 'address_code' => 'SITE2']);
        $movement = $this->movement($user, $customer, $depot);
        $movement->update(['movement_type' => 'site_to_site']);

        $route = [
            [MovementActionType::Collection, $depot, '08:00', '08:15'],
            [MovementActionType::Delivery, $siteOne, '09:00', '09:15'],
            [MovementActionType::Collection, $siteOne, '09:15', '09:30'],
            [MovementActionType::Delivery, $siteTwo, '10:15', '10:30'],
            [MovementActionType::Collection, $siteTwo, '10:30', '10:45'],
            [MovementActionType::Delivery, $depot, '11:30', '11:45'],
        ];
        $actions = collect($route)->map(fn ($row, $index) => $movement->actions()->create([
            'sequence' => $index + 1,
            'action_type' => $row[0],
            'site_id' => $row[1]->id,
            'schedule_start' => "2026-08-24 {$row[2]}",
            'schedule_end' => "2026-08-24 {$row[3]}",
        ]));

        foreach ([[0, 1], [2, 3], [4, 5]] as $index => [$collectionIndex, $deliveryIndex]) {
            $movement->items()->create([
                'description' => 'Machine '.($index + 1),
                'movement_action' => 'delivery',
                'movement_action_id' => $actions[$deliveryIndex]->id,
                'collection_action_id' => $actions[$collectionIndex]->id,
                'delivery_action_id' => $actions[$deliveryIndex]->id,
            ]);
        }

        $scheduled = app(MovementWorkflow::class)->transition($movement, MovementStatus::Scheduled, $user);

        $this->assertSame(MovementStatus::Scheduled, $scheduled->status);
        $this->assertCount(6, $scheduled->actions);
        $this->assertCount(3, $scheduled->items);
    }

    public function test_invalid_status_jump_is_rejected(): void
    {
        $u = User::factory()->create();
        $c = Customer::create(['account_number' => 'C3', 'name' => 'Customer']);
        $s = Site::create(['name' => 'Yard', 'address_line_1' => 'Road', 'postcode' => 'PE1', 'address_code' => 'YARD3']);
        $m = $this->movement($u, $c, $s);
        $this->expectException(ValidationException::class);
        app(MovementWorkflow::class)->transition($m, MovementStatus::Completed, $u);
    }
}
