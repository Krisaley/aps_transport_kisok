<?php

namespace Tests\Feature\Database;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MakeModelSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_requested_makes_and_models_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(9, DB::table('makes')->count());
        $this->assertSame(93, DB::table('models')->count());

        $this->assertDatabaseHas('makes', ['name' => 'JLG']);
        $this->assertDatabaseHas('makes', ['name' => 'Youngman (BoSS)']);
        $this->assertDatabaseHas('makes', ['name' => 'ToughLift']);

        $this->assertModelBelongsToMake('Power Tower', 'JLG');
        $this->assertModelBelongsToMake('BoSS PA-lift', 'Youngman (BoSS)');
        $this->assertModelBelongsToMake('ML-25', 'ToughLift');
        $this->assertModelBelongsToMake('GML 800+', 'Wienold');
    }

    private function assertModelBelongsToMake(string $model, string $make): void
    {
        $exists = DB::table('models')
            ->join('makes', 'makes.id', '=', 'models.make_id')
            ->where('models.name', $model)
            ->where('makes.name', $make)
            ->exists();

        $this->assertTrue($exists, "Expected model [{$model}] to belong to make [{$make}].");
    }
}
