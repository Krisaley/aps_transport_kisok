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
        $first->actions()->create(['sequence' => 1, 'action_type' => MovementActionType::Delivery, 'site_id' => $s->id, 'driver_id' => $d->id, 'schedule_start' => '2026-08-24 09:00', 'schedule_end' => '2026-08-24 10:00']);
        app(MovementWorkflow::class)->transition($first, MovementStatus::Scheduled, $u);
        $second = $this->movement($u, $c, $s);
        $second->actions()->create(['sequence' => 1, 'action_type' => MovementActionType::Collection, 'site_id' => $s->id, 'driver_id' => $d->id, 'schedule_start' => '2026-08-24 09:30', 'schedule_end' => '2026-08-24 10:30']);
        $this->expectException(ValidationException::class);
        app(MovementWorkflow::class)->transition($second, MovementStatus::Scheduled, $u);
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
