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
            ['email' => config('demo.email')],
            [
                'name' => config('demo.name'),
                'password' => config('demo.password'),
                'email_verified_at' => now(),
            ],
        );

        if ($demo->companies()->doesntExist()) {
            $this->call(CompanySeeder::class);
        }
    }
}
