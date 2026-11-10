<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use Symfony\Component\Intl\Currencies;

/**
 * Commercial rounding of monetary amounts (DIN 1333): halves are rounded away from zero.
 *
 * Use this for every amount that is displayed or summed up, e.g. invoice lines, subtotals or tax amounts.
 * Each level (line, subtotal, tax, total) has to be rounded and the next level must be calculated
 * from the already rounded values, so the numbers on a document always add up (see EN 16931).
 */
final class MoneyRounding
{
    public const DEFAULT_FRACTION_DIGITS = 2;

    private function __construct()
    {
    }

    /**
     * Rounds the amount to the precision of the given currency, or 2 decimals if no currency is given.
     */
    public static function round(float $amount, ?string $currency = null): float
    {
        $rounded = round($amount, self::getFractionDigits($currency), PHP_ROUND_HALF_UP);

        // prevent "-0.00" in rendered documents
        if ($rounded === 0.0) {
            return 0.0;
        }

        return $rounded;
    }

    /**
     * Returns the amount of decimals for the given currency (e.g. 2 for EUR, 0 for JPY, 3 for KWD).
     */
    public static function getFractionDigits(?string $currency = null): int
    {
        if ($currency === null || $currency === '') {
            return self::DEFAULT_FRACTION_DIGITS;
        }

        return Currencies::getFractionDigits(strtoupper($currency));
    }
}
