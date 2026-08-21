<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::updateOrCreate(['code' => 'APS'], [
            'name' => 'APS',
            'address' => 'Leewood Business Park, Norwood Way, Upton, PE28 5YQ',
            'email' => 'sales@accessplatforms.co.uk',
            'phone' => '01480 891 251',
            'document_prefix' => 'APS',
        ]);

        Company::updateOrCreate(['code' => 'APSR'], [
            'name' => 'APSR',
            'document_prefix' => 'APSR',
        ]);
    }
}
