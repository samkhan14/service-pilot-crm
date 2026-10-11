<?php

use App\Support\Money;

test('money normalizes and adds values with bcmath scale', function () {
    expect(Money::of(10))->toBe('10.00')
        ->and(Money::add('10.1', '0.2'))->toBe('10.30')
        ->and(Money::subtract('10.00', '3.33'))->toBe('6.67')
        ->and(Money::multiply('2.50', '3'))->toBe('7.50');
});

test('money percent and comparison helpers', function () {
    expect(Money::percent('200.00', '8.25'))->toBe('16.50')
        ->and(Money::compare('10.00', '10'))->toBe(0)
        ->and(Money::isZero('0.00'))->toBeTrue()
        ->and(Money::isNegative('-1.00'))->toBeTrue()
        ->and(Money::max('1.00', '2.50'))->toBe('2.50');
});
