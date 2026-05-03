<?php

namespace App\Helpers;

class NumberHelper
{
    public static function currency(float $amount, string $symbol = '₱'): string
    {
        return $symbol . number_format($amount, 2);
    }

    public static function percent(float $value, int $decimals = 1): string
    {
        return number_format($value, $decimals) . '%';
    }

    public static function compact(float $n): string
    {
        if ($n >= 1_000_000) return number_format($n / 1_000_000, 1) . 'M';
        if ($n >= 1_000)     return number_format($n / 1_000, 1) . 'K';
        return number_format($n, 2);
    }
}
