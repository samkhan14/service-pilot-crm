<?php

namespace Database\Factories;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->name(),
            'company_name' => fake()->optional()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('555-01##'),
            'status' => CustomerStatus::Active,
            'communication_preference' => 'any',
            'billing_city' => fake()->city(),
            'billing_region' => fake()->stateAbbr(),
            'billing_postal_code' => fake()->postcode(),
            'billing_country' => 'US',
        ];
    }
}
