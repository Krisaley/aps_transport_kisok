<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed only application-managed reference and authorization data.
     *
     * Never add demo, customer, tenant, equipment, or user seeders here.
     * Every included seeder must be safe to run repeatedly on a live database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            MakeSeeder::class,
            ModelSeeder::class,
        ]);
    }
}
