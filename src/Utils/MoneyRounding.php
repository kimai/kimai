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
    private const SIGNIFICANT_DIGITS = 15;

    private function __construct()
    {
    }

    /**
     * Rounds the amount to the precision of the given currency, or 2 decimals if no currency is given.
     */
    public static function round(float $amount, ?string $currency = null): float
    {
        return self::roundToDecimals($amount, self::getFractionDigits($currency));
    }

    /**
     * Commercial rounding to the given amount of decimals, robust against floating point noise.
     *
     * Use this only if you need a precision that does not depend on a currency (e.g. stored rates with 4 decimals).
     */
    public static function roundToDecimals(float $amount, int $decimals): float
    {
        // Remove floating point noise first, e.g. 103.75 * 0.42 = 43.574999999999996 instead of 43.575.
        // PHP 8.2 and 8.3 did such a "pre-rounding" internally and return 43.58, PHP 8.5 returns 43.57.
        // Pre-rounding to 15 significant digits (the precision of a double) gives the same result on all versions.
        if ($amount !== 0.0 && is_finite($amount)) {
            $precision = self::SIGNIFICANT_DIGITS - 1 - (int) floor(log10(abs($amount)));
            if ($precision > $decimals) {
                $amount = round($amount, $precision, PHP_ROUND_HALF_UP);
            }
        }

        $rounded = round($amount, $decimals, PHP_ROUND_HALF_UP);

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
