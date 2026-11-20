<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\JobApplication;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::factory()
            ->count(10)
            ->has(
                JobApplication::factory()
                    ->count(3)
                    ->sequence(
                        ['status' => ApplicationStatus::Wishlist],
                        ['status' => ApplicationStatus::Applied, 'applied_on' => now()->subDays(5)],
                        ['status' => ApplicationStatus::Interviewing, 'applied_on' => now()->subDays(12)],
                    ),
            )
            ->create();
    }
}
