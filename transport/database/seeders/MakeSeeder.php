<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MakeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = now();

        $makes = [
            'JLG',
            'Hinowa',
            'Genie',
            'Skyjack',
            'Niftylift',
            'LGMG',
            'Youngman (BoSS)',
            'ToughLift',
            'Wienold',
        ];

        DB::table('makes')->upsert(
            array_map(fn (string $name): array => [
                'name' => $name,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $makes),
            ['name'],
            ['updated_at'],
        );
    }
}
