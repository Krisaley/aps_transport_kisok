<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('app.bootstrap_admin_email');
        if (blank($email)) {
            $this->command->warn('BOOTSTRAP_ADMIN_EMAIL is not set; no login account was seeded.');

            return;
        } $company = Company::where('code', 'APS')->firstOrFail();
        $admin = User::updateOrCreate(['email' => $email], ['name' => config('app.bootstrap_admin_name', 'System Administrator'), 'company_id' => $company->id, 'password' => Hash::make(config('app.bootstrap_admin_password') ?: Str::password(32)), 'email_verified_at' => now(), 'is_active' => true]);
        $admin->syncRoles(['Super-Admin']);
        $admin->companies()->syncWithoutDetaching(Company::pluck('id'));
    }
}
