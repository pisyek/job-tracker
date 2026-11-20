<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $salaryMin = fake()->numberBetween(30, 60) * 100;

        return [
            'company_id' => Company::factory(),
            'role_title' => fake()->randomElement([
                'Junior Laravel Developer', 'Junior PHP Developer', 'Backend Developer',
                'Full-Stack Developer', 'Software Engineer (Graduate)',
            ]),
            'status' => ApplicationStatus::Wishlist,
            'salary_min' => $salaryMin,
            'salary_max' => $salaryMin + fake()->numberBetween(5, 20) * 100,
            'applied_on' => null,
            'notes' => fake()->optional()->randomElement([
                'Found on the company careers page.',
                'Referred by a friend from the cohort.',
                'Recruiter messaged me on LinkedIn.',
                'They asked for a GitHub link.',
            ]),
        ];
    }
}
