<?php

namespace Database\Factories;

use App\Enums\EstimateStatus;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estimate>
 */
class EstimateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'reference' => 'EST-'.fake()->unique()->bothify('TEST-####'),
            'customer_id' => fn (array $attributes) => Customer::factory()->create([
                'team_id' => $attributes['team_id'],
            ])->id,
            'status' => EstimateStatus::Draft,
            'version' => 1,
            'currency' => 'USD',
            'subtotal' => '100.00',
            'discount_amount' => '0.00',
            'tax_rate' => '0.000',
            'tax_amount' => '0.00',
            'total' => '100.00',
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => EstimateStatus::Sent,
            'sent_at' => now(),
            'issued_on' => now()->toDateString(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => EstimateStatus::Approved,
            'approved_at' => now(),
            'approved_by_name' => 'Factory Approver',
        ]);
    }
}
