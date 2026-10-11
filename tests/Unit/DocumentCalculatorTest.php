<?php

use App\Services\DocumentCalculator;

test('calculator totals lines with tax and capped discount', function () {
    $totals = app(DocumentCalculator::class)->calculate([
        ['description' => 'Labor', 'quantity' => 2, 'unit_price' => 50, 'tax_rate' => 10],
        ['description' => 'Part', 'quantity' => 1, 'unit_price' => 20, 'tax_rate' => 0],
    ], '15.00');

    expect($totals['subtotal'])->toBe('120.00')
        ->and($totals['discount_amount'])->toBe('15.00')
        ->and($totals['tax_amount'])->toBe('10.00')
        ->and($totals['total'])->toBe('115.00')
        ->and($totals['lines'])->toHaveCount(2);
});

test('calculator uses fallback tax rate when a line omits it', function () {
    $totals = app(DocumentCalculator::class)->calculate([
        ['description' => 'Visit', 'quantity' => 1, 'unit_price' => 100],
    ], '0', '8.25');

    expect($totals['tax_amount'])->toBe('8.25')
        ->and($totals['total'])->toBe('108.25');
});
