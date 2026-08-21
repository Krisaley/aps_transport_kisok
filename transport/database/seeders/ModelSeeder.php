<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelSeeder extends Seeder
{
    use WithoutModelEvents;
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modelsByMake = [
            'JLG' => [
                '1930ES',
                '2032ES',
                '2632ES',
                '2646ES',
                '3246ES',
                '4045R',
                'Ecolift',
                'Nano 35',
                'Nano SP',
                'Pecolift',
                'Power Tower',
                'Power Tower Duo',
            ],
            'Hinowa' => [
                'Lightlift 15.70',
                'Lightlift 17.75 MK3',
                'Lightlift 18.80',
                'Lightlift 20.10 MK3',
                'Lightlift 26.14 MK2',
                'Lightlift 33.17 MK2',
                'Lightlift 40.18',
            ],
            'Genie' => [
                'AWP-20S',
                'AWP-25S',
                'AWP-30S',
                'AWP-36S',
                'AWP-40S',
                'GR-15',
                'GR-20',
                'GS-1432m',
                'GS-1932m',
                'GS-1932',
                'GS-2032',
                'GS-2632',
                'GS-2646',
                'GS-3232',
                'GS-3246',
                'GS-4046',
                'SLA-10',
                'SLA-15',
                'SLA-20',
                'SLA-25',
            ],
            'Skyjack' => [
                'SJ12 E',
                'SJ16 E',
                'SJ20 E',
                'SJ3213 micro',
                'SJ3215 E',
                'SJ3219 E',
                'SJ3219 micro',
                'SJ3220 E',
                'SJ3226 E',
                'SJ3232 E',
                'SJ4726 E',
                'SJ4732 E',
                'SJ4740 E',
                'SJ6826 RT',
                'SJ6832 RT',
            ],
            'Niftylift' => [
                'HR12LE',
                'HR12N',
                'HR12 4x4',
                'HR15N',
                'HR15E',
                'HR15 4x4',
                'HR17N',
                'HR17E',
                'HR17 4x4',
                'HR21E',
                'HR21 4x4',
                'HR22SE',
                'HR28 4x4',
            ],
            'LGMG' => [
                'SS0407E',
                'SS0507E',
                'SS0607E',
                'AS0607',
                'AS0607W',
                'AS0607WE',
                'AS0607E',
                'AS0608E',
                'AS0808E',
                'AS0812E',
                'AS1012E',
                'AS1212E',
                'AS1413',
            ],
            'Youngman (BoSS)' => [
                'BoSS PA-lift',
            ],
            'ToughLift' => [
                'ML-10',
                'ML-15',
                'ML-20',
                'ML-25',
            ],
            'Wienold' => [
                'GML 500+',
                'GML 800+',
                'GML-C',
                'MFC 750',
                'SLK',
                'WLU-P /Ks 2.8',
                'WLU-P /Ks 4.0',
                'WLU-P /Ks 5.1',
            ],
        ];

        $makeIds = DB::table('makes')
            ->whereIn('name', array_keys($modelsByMake))
            ->pluck('id', 'name');

        $missingMakes = array_diff(array_keys($modelsByMake), $makeIds->keys()->all());

        if ($missingMakes !== []) {
            throw new \RuntimeException(
                'Cannot seed models because these makes are missing: '.implode(', ', $missingMakes),
            );
        }

        $timestamp = now();
        $models = [];

        foreach ($modelsByMake as $make => $names) {
            foreach ($names as $name) {
                $models[] = [
                    'make_id' => $makeIds[$make],
                    'name' => $name,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        DB::table('models')->upsert(
            $models,
            ['name', 'make_id'],
            ['updated_at'],
        );
    }
}
