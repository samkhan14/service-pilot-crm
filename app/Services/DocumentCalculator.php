<?php

namespace App\Services;

use App\Support\Money;

class DocumentCalculator
{
    /**
     * @param  array<int, array{description?: string, quantity: mixed, unit_price: mixed, tax_rate?: mixed}>  $lines
     * @return array{subtotal: string, tax_amount: string, total: string, lines: array<int, array{description: string, quantity: string, unit_price: string, tax_rate: string, line_total: string}>}
     */
    public function calculate(array $lines, string $discountAmount = '0.00', ?string $fallbackTaxRate = null): array
    {
        $subtotal = '0.00';
        $taxAmount = '0.00';
        $normalized = [];

        foreach (array_values($lines) as $index => $line) {
            $quantity = Money::of($line['quantity'] ?? 1);
            $unitPrice = Money::of($line['unit_price'] ?? 0);
            $taxRate = Money::of($line['tax_rate'] ?? $fallbackTaxRate ?? 0);
            $lineSubtotal = Money::multiply($quantity, $unitPrice);
            $lineTax = Money::percent($lineSubtotal, $taxRate);
            $lineTotal = Money::add($lineSubtotal, $lineTax);

            $subtotal = Money::add($subtotal, $lineSubtotal);
            $taxAmount = Money::add($taxAmount, $lineTax);

            $normalized[] = [
                'position' => $index,
                'description' => (string) ($line['description'] ?? ''),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'line_total' => $lineTotal,
                'is_material' => (bool) ($line['is_material'] ?? false),
            ];
        }

        $discount = Money::of($discountAmount);

        if (Money::compare($discount, $subtotal) > 0) {
            $discount = $subtotal;
        }

        $total = Money::add(Money::subtract($subtotal, $discount), $taxAmount);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'lines' => $normalized,
        ];
    }
}
