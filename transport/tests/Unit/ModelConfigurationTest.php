<?php

namespace Tests\Unit;

use App\Models\Equipment;
use App\Models\EquipmentModel;
use App\Models\Movement;
use App\Models\MovementItem;
use App\Models\MovementItemAccessory;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class ModelConfigurationTest extends TestCase
{
    public function test_non_standard_model_name_uses_the_models_table(): void
    {
        $this->assertSame('models', (new EquipmentModel)->getTable());
        $this->assertSame('equipment', (new Equipment)->getTable());
        $this->assertSame('movement_item_accessories', (new MovementItemAccessory)->getTable());
    }

    public function test_operational_attributes_are_cast_to_domain_types(): void
    {
        $vehicle = new Vehicle(['is_active' => 0]);
        $item = new MovementItem(['quantity' => 1.5, 'completed' => 1]);
        $movement = new Movement(['planned_date' => '2026-08-18']);

        $this->assertFalse($vehicle->is_active);
        $this->assertSame('1.50', $item->quantity);
        $this->assertTrue($item->completed);
        $this->assertSame('2026-08-18', $movement->planned_date->toDateString());
        $this->assertContains(SoftDeletes::class, class_uses_recursive($movement));
    }
}
