<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\ServiceLocation;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceLocation>
 */
class ServiceLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'customer_id' => Customer::factory(),
            'label' => 'Primary',
            'address_line1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'region' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'country' => 'US',
            'is_primary' => true,
        ];
    }
}
