<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'reference' => 'INV-'.fake()->unique()->bothify('TEST-####'),
            'customer_id' => fn (array $attributes) => Customer::factory()->create([
                'team_id' => $attributes['team_id'],
            ])->id,
            'status' => InvoiceStatus::Issued,
            'currency' => 'USD',
            'subtotal' => '100.00',
            'tax_amount' => '0.00',
            'total' => '100.00',
            'amount_paid' => '0.00',
            'balance_due' => '100.00',
            'issued_on' => now()->toDateString(),
            'due_on' => now()->addDays(14)->toDateString(),
        ];
    }
}
