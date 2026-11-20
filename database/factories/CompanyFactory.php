<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'website' => fake()->optional()->url(),
            'location' => fake()->randomElement([
                'Kuala Lumpur', 'Petaling Jaya', 'Cyberjaya', 'Penang', 'Johor Bahru', 'Remote',
            ]),
        ];
    }
}
