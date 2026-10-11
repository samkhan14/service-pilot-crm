<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Team;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'reference' => 'WO-'.fake()->unique()->bothify('TEST-####'),
            'customer_id' => fn (array $attributes) => Customer::factory()->create([
                'team_id' => $attributes['team_id'],
            ])->id,
            'status' => WorkOrderStatus::Draft,
            'priority' => Priority::Normal,
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'currency' => 'USD',
            'subtotal' => '100.00',
            'tax_amount' => '0.00',
            'total' => '100.00',
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => WorkOrderStatus::Scheduled,
            'scheduled_start_at' => now()->addDay()->setTime(9, 0),
            'scheduled_end_at' => now()->addDay()->setTime(11, 0),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => WorkOrderStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
