<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $demo = User::firstOrCreate(
            ['email' => env('DEMO_EMAIL', 'demo@example.com')],
            [
                'name' => env('DEMO_NAME', 'Demo User'),
                'password' => env('DEMO_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ],
        );

        if ($demo->companies()->doesntExist()) {
            $this->call(CompanySeeder::class);
        }
    }
}
