<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $tenant = Tenant::firstOrCreate([
            'name' => config('seed.tenant_name'),
        ]);

        User::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email' => config('seed.admin_email'),
            ],
            [
                'name' => config('seed.admin_name'),
                'password' => Hash::make(config('seed.admin_password')),
                'email_verified_at' => now(),
            ]
        );
    }
}
