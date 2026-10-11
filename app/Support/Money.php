<?php

namespace App\Support;

final class Money
{
    public static function of(int|float|string $value): string
    {
        if (is_int($value)) {
            return number_format($value, 2, '.', '');
        }

        if (is_float($value)) {
            return bcadd('0', number_format($value, 2, '.', ''), 2);
        }

        $normalized = trim($value);

        if ($normalized === '' || ! is_numeric($normalized)) {
            return '0.00';
        }

        return bcadd('0', $normalized, 2);
    }

    public static function add(int|float|string $left, int|float|string $right): string
    {
        return bcadd(self::of($left), self::of($right), 2);
    }

    public static function subtract(int|float|string $left, int|float|string $right): string
    {
        return bcsub(self::of($left), self::of($right), 2);
    }

    public static function multiply(int|float|string $left, int|float|string $right): string
    {
        return bcmul(self::of($left), self::of($right), 2);
    }

    public static function percent(int|float|string $amount, int|float|string $rate): string
    {
        return bcmul(self::of($amount), bcdiv(self::of($rate), '100', 6), 2);
    }

    public static function compare(int|float|string $left, int|float|string $right): int
    {
        return bccomp(self::of($left), self::of($right), 2);
    }

    public static function max(int|float|string $left, int|float|string $right): string
    {
        return self::compare($left, $right) >= 0 ? self::of($left) : self::of($right);
    }

    public static function isZero(int|float|string $value): bool
    {
        return self::compare($value, '0') === 0;
    }

    public static function isNegative(int|float|string $value): bool
    {
        return self::compare($value, '0') < 0;
    }
}
