<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\ServiceRequestStatus;
use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'reference' => 'SR-'.fake()->unique()->bothify('TEST-####'),
            'customer_id' => fn (array $attributes) => Customer::factory()->create([
                'team_id' => $attributes['team_id'],
            ])->id,
            'status' => ServiceRequestStatus::New,
            'priority' => Priority::Normal,
            'source' => 'manual',
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
        ];
    }
}
