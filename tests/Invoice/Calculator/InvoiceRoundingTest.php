<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Calculator;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Entity\Tax;
use App\Entity\TaxType;
use App\Entity\Timesheet;
use App\Invoice\Calculator\AbstractCalculator;
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\Calculator\PriceInvoiceCalculator;
use App\Invoice\TaxRow;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use App\Timesheet\RateCalculator\ClassicRateCalculator;
use App\Timesheet\RateCalculator\DecimalRateCalculator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Full invoice calculations with many durations and hourly rates, for both rate calculator modes.
 *
 * Rules that must hold for every invoice:
 * - every line is rounded to the currency precision
 * - the subtotal is the sum of all (rounded) lines
 * - the tax is the sum of all (rounded) tax rows
 * - the total is subtotal + tax
 */
#[CoversClass(AbstractCalculator::class)]
#[CoversClass(DefaultCalculator::class)]
#[CoversClass(PriceInvoiceCalculator::class)]
#[CoversClass(TaxRow::class)]
class InvoiceRoundingTest extends TestCase
{
    /**
     * Entries are [duration in seconds, hourly rate, fixed rate].
     *
     * @return iterable<string, array{string, string, array<float>, array<array{int, ?float, ?float}>, array<float>, float, float, float, int}>
     */
    public static function getInvoiceData(): iterable
    {
        yield '3 minute multiples, even rates (classic)' => [
            'classic',
            'default',
            [19.0],
            [[180, 60.0, null], [360, 60.0, null], [540, 80.0, null], [720, 80.0, null], [1260, 100.0, null], [1620, 100.0, null], [1980, 120.0, null], [3420, 150.0, null]],
            [3.0, 6.0, 12.0, 16.0, 35.0, 45.0, 66.0, 142.5],
            325.5,
            61.85,
            387.35,
            10080,
        ];

        yield '3 minute multiples, even rates (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[180, 60.0, null], [360, 60.0, null], [540, 80.0, null], [720, 80.0, null], [1260, 100.0, null], [1620, 100.0, null], [1980, 120.0, null], [3420, 150.0, null]],
            [3.0, 6.0, 12.0, 16.0, 35.0, 45.0, 66.0, 142.5],
            325.5,
            61.85,
            387.35,
            10080,
        ];

        yield '15 minute multiples, 90 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[900, 90.0, null], [1800, 90.0, null], [2700, 90.0, null], [3600, 90.0, null], [4500, 90.0, null], [5400, 90.0, null], [6300, 90.0, null], [28800, 90.0, null]],
            [22.5, 45.0, 67.5, 90.0, 112.5, 135.0, 157.5, 720.0],
            1350.0,
            256.5,
            1606.5,
            54000,
        ];

        yield '15 minute multiples, 90 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[900, 90.0, null], [1800, 90.0, null], [2700, 90.0, null], [3600, 90.0, null], [4500, 90.0, null], [5400, 90.0, null], [6300, 90.0, null], [28800, 90.0, null]],
            [22.5, 45.0, 67.5, 90.0, 112.5, 135.0, 157.5, 720.0],
            1350.0,
            256.5,
            1606.5,
            54000,
        ];

        yield '30 and 60 minute multiples, 75 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1800, 75.0, null], [3600, 75.0, null], [5400, 75.0, null], [7200, 75.0, null], [9000, 75.0, null], [27000, 75.0, null], [36000, 75.0, null]],
            [37.5, 75.0, 112.5, 150.0, 187.5, 562.5, 750.0],
            1875.0,
            356.25,
            2231.25,
            90000,
        ];

        yield '30 and 60 minute multiples, 75 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1800, 75.0, null], [3600, 75.0, null], [5400, 75.0, null], [7200, 75.0, null], [9000, 75.0, null], [27000, 75.0, null], [36000, 75.0, null]],
            [37.5, 75.0, 112.5, 150.0, 187.5, 562.5, 750.0],
            1875.0,
            356.25,
            2231.25,
            90000,
        ];

        yield 'full hours, mixed even rates (classic)' => [
            'classic',
            'default',
            [19.0],
            [[3600, 50.0, null], [7200, 80.0, null], [10800, 100.0, null], [14400, 120.0, null], [28800, 150.0, null], [36000, 200.0, null]],
            [50.0, 160.0, 300.0, 480.0, 1200.0, 2000.0],
            4190.0,
            796.1,
            4986.1,
            100800,
        ];

        yield 'full hours, mixed even rates (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[3600, 50.0, null], [7200, 80.0, null], [10800, 100.0, null], [14400, 120.0, null], [28800, 150.0, null], [36000, 200.0, null]],
            [50.0, 160.0, 300.0, 480.0, 1200.0, 2000.0],
            4190.0,
            796.1,
            4986.1,
            100800,
        ];

        yield '3 minute multiples, 85 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[180, 85.0, null], [540, 85.0, null], [900, 85.0, null], [2340, 85.0, null], [3780, 85.0, null], [5220, 85.0, null], [6660, 85.0, null], [10980, 85.0, null]],
            [4.25, 12.75, 21.25, 55.25, 89.25, 123.25, 157.25, 259.25],
            722.5,
            137.28,
            859.78,
            30600,
        ];

        yield '3 minute multiples, 85 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[180, 85.0, null], [540, 85.0, null], [900, 85.0, null], [2340, 85.0, null], [3780, 85.0, null], [5220, 85.0, null], [6660, 85.0, null], [10980, 85.0, null]],
            [4.25, 12.75, 21.25, 55.25, 89.25, 123.25, 157.25, 259.25],
            722.5,
            137.28,
            859.78,
            30600,
        ];

        yield '3 minute multiples, 95 and 110 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[180, 95.0, null], [1080, 95.0, null], [3060, 95.0, null], [180, 110.0, null], [1440, 110.0, null], [4140, 110.0, null]],
            [4.75, 28.5, 80.75, 5.5, 44.0, 126.5],
            290.0,
            55.1,
            345.1,
            10080,
        ];

        yield '3 minute multiples, 95 and 110 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[180, 95.0, null], [1080, 95.0, null], [3060, 95.0, null], [180, 110.0, null], [1440, 110.0, null], [4140, 110.0, null]],
            [4.75, 28.5, 80.75, 5.5, 44.0, 126.5],
            290.0,
            55.1,
            345.1,
            10080,
        ];

        yield '3 minute multiples, 103.75 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[180, 103.75, null], [540, 103.75, null], [900, 103.75, null], [1260, 103.75, null], [1620, 103.75, null], [1980, 103.75, null], [2700, 103.75, null], [3420, 103.75, null]],
            [5.19, 15.56, 25.94, 36.31, 46.69, 57.06, 77.81, 98.56],
            363.12,
            68.99,
            432.11,
            12600,
        ];

        yield '3 minute multiples, 103.75 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[180, 103.75, null], [540, 103.75, null], [900, 103.75, null], [1260, 103.75, null], [1620, 103.75, null], [1980, 103.75, null], [2700, 103.75, null], [3420, 103.75, null]],
            [5.19, 15.56, 25.94, 36.31, 46.69, 57.06, 77.81, 98.56],
            363.12,
            68.99,
            432.11,
            12600,
        ];

        yield '15 minute multiples, 103.75 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[900, 103.75, null], [1800, 103.75, null], [2700, 103.75, null], [4500, 103.75, null], [5400, 103.75, null], [8100, 103.75, null], [27000, 103.75, null]],
            [25.94, 51.88, 77.81, 129.69, 155.63, 233.44, 778.13],
            1452.52,
            275.98,
            1728.5,
            50400,
        ];

        yield '15 minute multiples, 103.75 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[900, 103.75, null], [1800, 103.75, null], [2700, 103.75, null], [4500, 103.75, null], [5400, 103.75, null], [8100, 103.75, null], [27000, 103.75, null]],
            [25.94, 51.88, 77.81, 129.69, 155.63, 233.44, 778.13],
            1452.52,
            275.98,
            1728.5,
            50400,
        ];

        yield '15 minute multiples, 72.25 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[900, 72.25, null], [2700, 72.25, null], [4500, 72.25, null], [6300, 72.25, null], [8100, 72.25, null], [9900, 72.25, null]],
            [18.06, 54.19, 90.31, 126.44, 162.56, 198.69],
            650.25,
            123.55,
            773.8,
            32400,
        ];

        yield '15 minute multiples, 72.25 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[900, 72.25, null], [2700, 72.25, null], [4500, 72.25, null], [6300, 72.25, null], [8100, 72.25, null], [9900, 72.25, null]],
            [18.06, 54.19, 90.31, 126.44, 162.56, 198.69],
            650.25,
            123.55,
            773.8,
            32400,
        ];

        yield '30 minute multiples, 85.50 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1800, 85.5, null], [5400, 85.5, null], [9000, 85.5, null], [12600, 85.5, null], [16200, 85.5, null], [19800, 85.5, null]],
            [42.75, 128.25, 213.75, 299.25, 384.75, 470.25],
            1539.0,
            292.41,
            1831.41,
            64800,
        ];

        yield '30 minute multiples, 85.50 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1800, 85.5, null], [5400, 85.5, null], [9000, 85.5, null], [12600, 85.5, null], [16200, 85.5, null], [19800, 85.5, null]],
            [42.75, 128.25, 213.75, 299.25, 384.75, 470.25],
            1539.0,
            292.41,
            1831.41,
            64800,
        ];

        yield 'customer case, 26 lines with 103.75 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[27000, 103.75, null], [27000, 103.75, null], [27000, 103.75, null], [27000, 103.75, null], [1800, 103.75, null], [1800, 103.75, null], [1800, 103.75, null], [1800, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [14400, 103.75, null], [14400, 103.75, null], [14400, 103.75, null], [14400, 103.75, null]],
            [778.13, 778.13, 778.13, 778.13, 51.88, 51.88, 51.88, 51.88, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 415.0, 415.0, 415.0, 415.0],
            12242.54,
            2326.08,
            14568.62,
            424800,
        ];

        yield 'customer case, 26 lines with 103.75 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[27000, 103.75, null], [27000, 103.75, null], [27000, 103.75, null], [27000, 103.75, null], [1800, 103.75, null], [1800, 103.75, null], [1800, 103.75, null], [1800, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [14400, 103.75, null], [14400, 103.75, null], [14400, 103.75, null], [14400, 103.75, null]],
            [778.13, 778.13, 778.13, 778.13, 51.88, 51.88, 51.88, 51.88, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 518.75, 415.0, 415.0, 415.0, 415.0],
            12242.54,
            2326.08,
            14568.62,
            424800,
        ];

        yield 'customer case, grouped by price (classic)' => [
            'classic',
            'price',
            [19.0],
            [[27000, 103.75, null], [27000, 103.75, null], [27000, 103.75, null], [27000, 103.75, null], [1800, 103.75, null], [1800, 103.75, null], [1800, 103.75, null], [1800, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [14400, 103.75, null], [14400, 103.75, null], [14400, 103.75, null], [14400, 103.75, null]],
            [12242.5],
            12242.5,
            2326.08,
            14568.58,
            424800,
        ];

        yield 'customer case, grouped by price (decimal)' => [
            'decimal',
            'price',
            [19.0],
            [[27000, 103.75, null], [27000, 103.75, null], [27000, 103.75, null], [27000, 103.75, null], [1800, 103.75, null], [1800, 103.75, null], [1800, 103.75, null], [1800, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [18000, 103.75, null], [14400, 103.75, null], [14400, 103.75, null], [14400, 103.75, null], [14400, 103.75, null]],
            [12242.5],
            12242.5,
            2326.08,
            14568.58,
            424800,
        ];

        yield '20 minutes, three times, 100 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1200, 100.0, null], [1200, 100.0, null], [1200, 100.0, null]],
            [33.33, 33.33, 33.33],
            99.99,
            19.0,
            118.99,
            3600,
        ];

        yield '20 minutes, three times, 100 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1200, 100.0, null], [1200, 100.0, null], [1200, 100.0, null]],
            [33.0, 33.0, 33.0],
            99.0,
            18.81,
            117.81,
            3564,
        ];

        yield '20 minutes, three times, grouped by price (classic)' => [
            'classic',
            'price',
            [19.0],
            [[1200, 100.0, null], [1200, 100.0, null], [1200, 100.0, null]],
            [100.0],
            100.0,
            19.0,
            119.0,
            3600,
        ];

        yield '20 minutes, three times, grouped by price (decimal)' => [
            'decimal',
            'price',
            [19.0],
            [[1200, 100.0, null], [1200, 100.0, null], [1200, 100.0, null]],
            [100.0],
            100.0,
            19.0,
            119.0,
            3600,
        ];

        yield '5, 10 and 40 minutes, 90 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[300, 90.0, null], [600, 90.0, null], [2400, 90.0, null], [300, 90.0, null], [600, 90.0, null], [2400, 90.0, null]],
            [7.5, 15.0, 60.0, 7.5, 15.0, 60.0],
            165.0,
            31.35,
            196.35,
            6600,
        ];

        yield '5, 10 and 40 minutes, 90 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[300, 90.0, null], [600, 90.0, null], [2400, 90.0, null], [300, 90.0, null], [600, 90.0, null], [2400, 90.0, null]],
            [7.2, 15.3, 60.3, 7.2, 15.3, 60.3],
            165.6,
            31.46,
            197.06,
            6624,
        ];

        yield '1, 7 and 13 minutes, 75 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[60, 75.0, null], [420, 75.0, null], [780, 75.0, null], [60, 75.0, null], [420, 75.0, null], [780, 75.0, null], [60, 75.0, null]],
            [1.25, 8.75, 16.25, 1.25, 8.75, 16.25, 1.25],
            53.75,
            10.21,
            63.96,
            2580,
        ];

        yield '1, 7 and 13 minutes, 75 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[60, 75.0, null], [420, 75.0, null], [780, 75.0, null], [60, 75.0, null], [420, 75.0, null], [780, 75.0, null], [60, 75.0, null]],
            [1.5, 9.0, 16.5, 1.5, 9.0, 16.5, 1.5],
            55.5,
            10.55,
            66.05,
            2664,
        ];

        yield '10 minutes, twelve times, 120 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null]],
            [20.0, 20.0, 20.0, 20.0, 20.0, 20.0, 20.0, 20.0, 20.0, 20.0, 20.0, 20.0],
            240.0,
            45.6,
            285.6,
            7200,
        ];

        yield '10 minutes, twelve times, 120 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null], [600, 120.0, null]],
            [20.4, 20.4, 20.4, 20.4, 20.4, 20.4, 20.4, 20.4, 20.4, 20.4, 20.4, 20.4],
            244.8,
            46.51,
            291.31,
            7344,
        ];

        yield '17:34 minutes, 21.34 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1054, 21.34, null], [1054, 21.34, null], [1054, 21.34, null], [1054, 21.34, null], [1054, 21.34, null]],
            [6.25, 6.25, 6.25, 6.25, 6.25],
            31.25,
            5.94,
            37.19,
            5270,
        ];

        yield '17:34 minutes, 21.34 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1054, 21.34, null], [1054, 21.34, null], [1054, 21.34, null], [1054, 21.34, null], [1054, 21.34, null]],
            [6.19, 6.19, 6.19, 6.19, 6.19],
            30.95,
            5.88,
            36.83,
            5220,
        ];

        yield '17:34 minutes, mixed odd rates (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1054, 21.34, null], [1054, 67.89, null], [1054, 99.99, null], [1054, 103.75, null], [1054, 133.33, null]],
            [6.25, 19.88, 29.27, 30.38, 39.04],
            124.82,
            23.72,
            148.54,
            5270,
        ];

        yield '17:34 minutes, mixed odd rates (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1054, 21.34, null], [1054, 67.89, null], [1054, 99.99, null], [1054, 103.75, null], [1054, 133.33, null]],
            [6.19, 19.69, 29.0, 30.09, 38.67],
            123.64,
            23.49,
            147.13,
            5220,
        ];

        yield 'odd durations, 21.34 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[433, 21.34, null], [1342, 21.34, null], [3599, 21.34, null], [3661, 21.34, null], [8542, 21.34, null], [12813, 21.34, null]],
            [2.57, 7.96, 21.33, 21.7, 50.64, 75.95],
            180.15,
            34.23,
            214.38,
            30390,
        ];

        yield 'odd durations, 21.34 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[433, 21.34, null], [1342, 21.34, null], [3599, 21.34, null], [3661, 21.34, null], [8542, 21.34, null], [12813, 21.34, null]],
            [2.56, 7.9, 21.34, 21.77, 50.58, 75.97],
            180.12,
            34.22,
            214.34,
            30384,
        ];

        yield 'odd durations, 67.89 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[92, 67.89, null], [346, 67.89, null], [1054, 67.89, null], [3438, 67.89, null], [107, 67.89, null], [13342, 67.89, null]],
            [1.74, 6.53, 19.88, 64.84, 2.02, 251.61],
            346.62,
            65.86,
            412.48,
            18379,
        ];

        yield 'odd durations, 67.89 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[92, 67.89, null], [346, 67.89, null], [1054, 67.89, null], [3438, 67.89, null], [107, 67.89, null], [13342, 67.89, null]],
            [2.04, 6.79, 19.69, 65.17, 2.04, 251.87],
            347.6,
            66.04,
            413.64,
            18432,
        ];

        yield 'odd durations, 99.99 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[618, 99.99, null], [1418, 99.99, null], [1818, 99.99, null], [3418, 99.99, null], [3818, 99.99, null]],
            [17.17, 39.39, 50.5, 94.94, 106.05],
            308.05,
            58.53,
            366.58,
            11090,
        ];

        yield 'odd durations, 99.99 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[618, 99.99, null], [1418, 99.99, null], [1818, 99.99, null], [3418, 99.99, null], [3818, 99.99, null]],
            [17.0, 39.0, 50.99, 94.99, 105.99],
            307.97,
            58.51,
            366.48,
            11088,
        ];

        yield 'odd durations, 103.75 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1494, 103.75, null], [1512, 103.75, null], [2490, 103.75, null], [2682, 103.75, null], [5733, 103.75, null]],
            [43.06, 43.58, 71.76, 77.29, 165.22],
            400.91,
            76.17,
            477.08,
            13911,
        ];

        yield 'odd durations, 103.75 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1494, 103.75, null], [1512, 103.75, null], [2490, 103.75, null], [2682, 103.75, null], [5733, 103.75, null]],
            [43.58, 43.58, 71.59, 77.81, 164.96],
            401.52,
            76.29,
            477.81,
            13932,
        ];

        yield 'odd durations, 47.11 and 33.33 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[793, 47.11, null], [1891, 47.11, null], [4627, 47.11, null], [793, 33.33, null], [1891, 33.33, null], [4627, 33.33, null]],
            [10.38, 24.75, 60.55, 7.34, 17.51, 42.84],
            163.37,
            31.04,
            194.41,
            14622,
        ];

        yield 'odd durations, 47.11 and 33.33 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[793, 47.11, null], [1891, 47.11, null], [4627, 47.11, null], [793, 33.33, null], [1891, 33.33, null], [4627, 33.33, null]],
            [10.36, 24.97, 60.77, 7.33, 17.66, 43.0],
            164.09,
            31.18,
            195.27,
            14688,
        ];

        yield 'odd durations, 12.34 and 17.77 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[213, 12.34, null], [2684, 12.34, null], [33355, 12.34, null], [213, 17.77, null], [2684, 17.77, null], [33355, 17.77, null]],
            [0.73, 9.2, 114.33, 1.05, 13.25, 164.64],
            303.2,
            57.61,
            360.81,
            72504,
        ];

        yield 'odd durations, 12.34 and 17.77 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[213, 12.34, null], [2684, 12.34, null], [33355, 12.34, null], [213, 17.77, null], [2684, 17.77, null], [33355, 17.77, null]],
            [0.74, 9.26, 114.39, 1.07, 13.33, 164.73],
            303.52,
            57.67,
            361.19,
            72576,
        ];

        yield 'seconds only, 133.33 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1, 133.33, null], [27, 133.33, null], [59, 133.33, null], [61, 133.33, null], [99, 133.33, null], [119, 133.33, null]],
            [0.04, 1.0, 2.19, 2.26, 3.67, 4.41],
            13.57,
            2.58,
            16.15,
            366,
        ];

        yield 'seconds only, 133.33 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1, 133.33, null], [27, 133.33, null], [59, 133.33, null], [61, 133.33, null], [99, 133.33, null], [119, 133.33, null]],
            [0.0, 1.33, 2.67, 2.67, 4.0, 4.0],
            14.67,
            2.79,
            17.46,
            396,
        ];

        yield 'many short entries, 21.34 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[71, 21.34, null], [142, 21.34, null], [213, 21.34, null], [284, 21.34, null], [355, 21.34, null], [366, 21.34, null], [437, 21.34, null], [508, 21.34, null], [579, 21.34, null], [650, 21.34, null]],
            [0.42, 0.84, 1.26, 1.68, 2.1, 2.17, 2.59, 3.01, 3.43, 3.85],
            21.35,
            4.06,
            25.41,
            3605,
        ];

        yield 'many short entries, 21.34 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[71, 21.34, null], [142, 21.34, null], [213, 21.34, null], [284, 21.34, null], [355, 21.34, null], [366, 21.34, null], [437, 21.34, null], [508, 21.34, null], [579, 21.34, null], [650, 21.34, null]],
            [0.43, 0.85, 1.28, 1.71, 2.13, 2.13, 2.56, 2.99, 3.41, 3.84],
            21.33,
            4.05,
            25.38,
            3600,
        ];

        yield 'tiny rate 0.99 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1054, 0.99, null], [1800, 0.99, null], [2700, 0.99, null], [3600, 0.99, null], [6000, 0.99, null]],
            [0.29, 0.5, 0.74, 0.99, 1.65],
            4.17,
            0.79,
            4.96,
            15154,
        ];

        yield 'tiny rate 0.99 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1054, 0.99, null], [1800, 0.99, null], [2700, 0.99, null], [3600, 0.99, null], [6000, 0.99, null]],
            [0.29, 0.5, 0.74, 0.99, 1.65],
            4.17,
            0.79,
            4.96,
            15156,
        ];

        yield 'high rate 1234.56 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1054, 1234.56, null], [900, 1234.56, null], [1980, 1234.56, null], [9600, 1234.56, null], [28800, 1234.56, null]],
            [361.45, 308.64, 679.01, 3292.16, 9876.48],
            14517.74,
            2758.37,
            17276.11,
            42334,
        ];

        yield 'high rate 1234.56 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1054, 1234.56, null], [900, 1234.56, null], [1980, 1234.56, null], [9600, 1234.56, null], [28800, 1234.56, null]],
            [358.02, 308.64, 679.01, 3296.28, 9876.48],
            14518.43,
            2758.5,
            17276.93,
            42336,
        ];

        yield 'one month, 160 hours with 103.75 (classic)' => [
            'classic',
            'default',
            [19.0],
            [[28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [27000, 103.75, null], [1800, 103.75, null]],
            [830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 778.13, 51.88],
            16600.01,
            3154.0,
            19754.01,
            576000,
        ];

        yield 'one month, 160 hours with 103.75 (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [28800, 103.75, null], [27000, 103.75, null], [1800, 103.75, null]],
            [830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 830.0, 778.13, 51.88],
            16600.01,
            3154.0,
            19754.01,
            576000,
        ];

        yield 'tax rate 0% (classic)' => [
            'classic',
            'default',
            [0.0],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.25, 47.52, 129.69, 33.33, 12.83],
            229.62,
            0.0,
            229.62,
            9814,
        ];

        yield 'tax rate 0% (decimal)' => [
            'decimal',
            'default',
            [0.0],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.19, 47.52, 129.69, 33.0, 12.83],
            229.23,
            0.0,
            229.23,
            9792,
        ];

        yield 'tax rate 7% (classic)' => [
            'classic',
            'default',
            [7.0],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.25, 47.52, 129.69, 33.33, 12.83],
            229.62,
            16.07,
            245.69,
            9814,
        ];

        yield 'tax rate 7% (decimal)' => [
            'decimal',
            'default',
            [7.0],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.19, 47.52, 129.69, 33.0, 12.83],
            229.23,
            16.05,
            245.28,
            9792,
        ];

        yield 'tax rate 8.1% (classic)' => [
            'classic',
            'default',
            [8.1],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.25, 47.52, 129.69, 33.33, 12.83],
            229.62,
            18.6,
            248.22,
            9814,
        ];

        yield 'tax rate 8.1% (decimal)' => [
            'decimal',
            'default',
            [8.1],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.19, 47.52, 129.69, 33.0, 12.83],
            229.23,
            18.57,
            247.8,
            9792,
        ];

        yield 'tax rate 16% (classic)' => [
            'classic',
            'default',
            [16.0],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.25, 47.52, 129.69, 33.33, 12.83],
            229.62,
            36.74,
            266.36,
            9814,
        ];

        yield 'tax rate 16% (decimal)' => [
            'decimal',
            'default',
            [16.0],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.19, 47.52, 129.69, 33.0, 12.83],
            229.23,
            36.68,
            265.91,
            9792,
        ];

        yield 'tax rate 20% (classic)' => [
            'classic',
            'default',
            [20.0],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.25, 47.52, 129.69, 33.33, 12.83],
            229.62,
            45.92,
            275.54,
            9814,
        ];

        yield 'tax rate 20% (decimal)' => [
            'decimal',
            'default',
            [20.0],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.19, 47.52, 129.69, 33.0, 12.83],
            229.23,
            45.85,
            275.08,
            9792,
        ];

        yield 'tax rate 21% (classic)' => [
            'classic',
            'default',
            [21.0],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.25, 47.52, 129.69, 33.33, 12.83],
            229.62,
            48.22,
            277.84,
            9814,
        ];

        yield 'tax rate 21% (decimal)' => [
            'decimal',
            'default',
            [21.0],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.19, 47.52, 129.69, 33.0, 12.83],
            229.23,
            48.14,
            277.37,
            9792,
        ];

        yield 'two tax rates 19% and 7% (classic)' => [
            'classic',
            'default',
            [19.0, 7.0],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.25, 47.52, 129.69, 33.33, 12.83],
            229.62,
            59.7,
            289.32,
            9814,
        ];

        yield 'two tax rates 19% and 7% (decimal)' => [
            'decimal',
            'default',
            [19.0, 7.0],
            [[1054, 21.34, null], [2520, 67.89, null], [4500, 103.75, null], [1200, 99.99, null], [540, 85.5, null]],
            [6.19, 47.52, 129.69, 33.0, 12.83],
            229.23,
            59.6,
            288.83,
            9792,
        ];

        yield 'tax rate 21% on 9.50 (classic)' => [
            'classic',
            'default',
            [21.0],
            [[1800, 19.0, null]],
            [9.5],
            9.5,
            2.0,
            11.5,
            1800,
        ];

        yield 'tax rate 21% on 9.50 (decimal)' => [
            'decimal',
            'default',
            [21.0],
            [[1800, 19.0, null]],
            [9.5],
            9.5,
            2.0,
            11.5,
            1800,
        ];

        yield 'fixed rates only (classic)' => [
            'classic',
            'default',
            [19.0],
            [[3600, null, 49.99], [3600, null, 120.0], [3600, null, 0.5], [3600, null, 1234.56]],
            [49.99, 120.0, 0.5, 1234.56],
            1405.05,
            266.96,
            1672.01,
            14400,
        ];

        yield 'fixed rates only (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[3600, null, 49.99], [3600, null, 120.0], [3600, null, 0.5], [3600, null, 1234.56]],
            [49.99, 120.0, 0.5, 1234.56],
            1405.05,
            266.96,
            1672.01,
            14400,
        ];

        yield 'fixed rates with 3 decimals (classic)' => [
            'classic',
            'default',
            [19.0],
            [[3600, null, 12.345], [3600, null, 0.125], [3600, null, 99.995]],
            [12.35, 0.13, 100.0],
            112.48,
            21.37,
            133.85,
            10800,
        ];

        yield 'fixed rates with 3 decimals (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[3600, null, 12.345], [3600, null, 0.125], [3600, null, 99.995]],
            [12.35, 0.13, 100.0],
            112.48,
            21.37,
            133.85,
            10800,
        ];

        yield 'fixed and hourly rates mixed (classic)' => [
            'classic',
            'default',
            [19.0],
            [[3600, null, 49.99], [1054, 21.34, null], [3600, null, 15.0], [1200, 100.0, null], [2700, 103.75, null]],
            [49.99, 6.25, 15.0, 33.33, 77.81],
            182.38,
            34.65,
            217.03,
            12154,
        ];

        yield 'fixed and hourly rates mixed (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[3600, null, 49.99], [1054, 21.34, null], [3600, null, 15.0], [1200, 100.0, null], [2700, 103.75, null]],
            [49.99, 6.19, 15.0, 33.0, 77.81],
            181.99,
            34.58,
            216.57,
            12132,
        ];

        yield 'issue 5700: 5, 10 and 15 minutes with 60 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[300, 60.0, null], [600, 60.0, null], [900, 60.0, null]],
            [5.0, 10.0, 15.0],
            30.0,
            5.7,
            35.7,
            1800,
        ];

        yield 'issue 5700: 5, 10 and 15 minutes with 60 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[300, 60.0, null], [600, 60.0, null], [900, 60.0, null]],
            [4.8, 10.2, 15.0],
            30.0,
            5.7,
            35.7,
            1800,
        ];

        yield 'issue 5700: 3 x 10 minutes with 75 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[600, 75.0, null], [600, 75.0, null], [600, 75.0, null]],
            [12.5, 12.5, 12.5],
            37.5,
            7.13,
            44.63,
            1800,
        ];

        yield 'issue 5700: 3 x 10 minutes with 75 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[600, 75.0, null], [600, 75.0, null], [600, 75.0, null]],
            [12.75, 12.75, 12.75],
            38.25,
            7.27,
            45.52,
            1836,
        ];

        yield 'issue 5700: 3 x 10 minutes with 75 per hour, grouped by price (classic)' => [
            'classic',
            'price',
            [19.0],
            [[600, 75.0, null], [600, 75.0, null], [600, 75.0, null]],
            [37.5],
            37.5,
            7.13,
            44.63,
            1800,
        ];

        yield 'issue 5700: 3 x 10 minutes with 75 per hour, grouped by price (decimal)' => [
            'decimal',
            'price',
            [19.0],
            [[600, 75.0, null], [600, 75.0, null], [600, 75.0, null]],
            [37.5],
            37.5,
            7.13,
            44.63,
            1800,
        ];

        yield 'issue 5730: 1 minute with 129360 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[60, 129360.0, null]],
            [2156.0],
            2156.0,
            409.64,
            2565.64,
            60,
        ];

        yield 'issue 5730: 1 minute with 129360 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[60, 129360.0, null]],
            [2587.2],
            2587.2,
            491.57,
            3078.77,
            72,
        ];

        yield 'PR 1424: 30 minutes vs. 2 x 15 minutes with 27.50 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1800, 27.5, null], [900, 27.5, null], [900, 27.5, null]],
            [13.75, 6.88, 6.88],
            27.51,
            5.23,
            32.74,
            3600,
        ];

        yield 'PR 1424: 30 minutes vs. 2 x 15 minutes with 27.50 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1800, 27.5, null], [900, 27.5, null], [900, 27.5, null]],
            [13.75, 6.88, 6.88],
            27.51,
            5.23,
            32.74,
            3600,
        ];

        yield 'PR 1424: 2 x 15 minutes with 27.50 per hour, grouped by price (classic)' => [
            'classic',
            'price',
            [19.0],
            [[900, 27.5, null], [900, 27.5, null]],
            [13.75],
            13.75,
            2.61,
            16.36,
            1800,
        ];

        yield 'PR 1424: 2 x 15 minutes with 27.50 per hour, grouped by price (decimal)' => [
            'decimal',
            'price',
            [19.0],
            [[900, 27.5, null], [900, 27.5, null]],
            [13.75],
            13.75,
            2.61,
            16.36,
            1800,
        ];

        yield 'issue 5268: 15 minutes with 32.50 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[900, 32.5, null]],
            [8.13],
            8.13,
            1.54,
            9.67,
            900,
        ];

        yield 'issue 5268: 15 minutes with 32.50 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[900, 32.5, null]],
            [8.13],
            8.13,
            1.54,
            9.67,
            900,
        ];

        yield 'issue 5341: 2 x 30 minutes with 32.25 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1800, 32.25, null], [1800, 32.25, null]],
            [16.13, 16.13],
            32.26,
            6.13,
            38.39,
            3600,
        ];

        yield 'issue 5341: 2 x 30 minutes with 32.25 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1800, 32.25, null], [1800, 32.25, null]],
            [16.13, 16.13],
            32.26,
            6.13,
            38.39,
            3600,
        ];

        yield 'issue 3539: 30 minutes with 99.75 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1800, 99.75, null], [1800, 99.75, null], [1800, 99.75, null]],
            [49.88, 49.88, 49.88],
            149.64,
            28.43,
            178.07,
            5400,
        ];

        yield 'issue 3539: 30 minutes with 99.75 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1800, 99.75, null], [1800, 99.75, null], [1800, 99.75, null]],
            [49.88, 49.88, 49.88],
            149.64,
            28.43,
            178.07,
            5400,
        ];

        yield 'issue 4678: day rate 105 as 13.125 per hour, 8 x 1 hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[3600, 13.125, null], [3600, 13.125, null], [3600, 13.125, null], [3600, 13.125, null], [3600, 13.125, null], [3600, 13.125, null], [3600, 13.125, null], [3600, 13.125, null]],
            [13.13, 13.13, 13.13, 13.13, 13.13, 13.13, 13.13, 13.13],
            105.04,
            19.96,
            125.0,
            28800,
        ];

        yield 'issue 4678: day rate 105 as 13.125 per hour, 8 x 1 hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[3600, 13.125, null], [3600, 13.125, null], [3600, 13.125, null], [3600, 13.125, null], [3600, 13.125, null], [3600, 13.125, null], [3600, 13.125, null], [3600, 13.125, null]],
            [13.13, 13.13, 13.13, 13.13, 13.13, 13.13, 13.13, 13.13],
            105.04,
            19.96,
            125.0,
            28800,
        ];

        yield 'issue 4678: day rate 105 as 13.13 per hour, 8 x 1 hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[3600, 13.13, null], [3600, 13.13, null], [3600, 13.13, null], [3600, 13.13, null], [3600, 13.13, null], [3600, 13.13, null], [3600, 13.13, null], [3600, 13.13, null]],
            [13.13, 13.13, 13.13, 13.13, 13.13, 13.13, 13.13, 13.13],
            105.04,
            19.96,
            125.0,
            28800,
        ];

        yield 'issue 4678: day rate 105 as 13.13 per hour, 8 x 1 hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[3600, 13.13, null], [3600, 13.13, null], [3600, 13.13, null], [3600, 13.13, null], [3600, 13.13, null], [3600, 13.13, null], [3600, 13.13, null], [3600, 13.13, null]],
            [13.13, 13.13, 13.13, 13.13, 13.13, 13.13, 13.13, 13.13],
            105.04,
            19.96,
            125.0,
            28800,
        ];

        yield 'PR 4853: 25 minutes with 34.34 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[1500, 34.34, null]],
            [14.31],
            14.31,
            2.72,
            17.03,
            1500,
        ];

        yield 'PR 4853: 25 minutes with 34.34 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[1500, 34.34, null]],
            [14.42],
            14.42,
            2.74,
            17.16,
            1512,
        ];

        yield 'issue 3282: 50 minutes with 100 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[3000, 100.0, null]],
            [83.33],
            83.33,
            15.83,
            99.16,
            3000,
        ];

        yield 'issue 3282: 50 minutes with 100 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[3000, 100.0, null]],
            [83.0],
            83.0,
            15.77,
            98.77,
            2988,
        ];

        yield 'issue 4581: 44 minutes with 65 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[2640, 65.0, null]],
            [47.67],
            47.67,
            9.06,
            56.73,
            2640,
        ];

        yield 'issue 4581: 44 minutes with 65 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[2640, 65.0, null]],
            [47.45],
            47.45,
            9.02,
            56.47,
            2628,
        ];

        yield 'old UtilTest: 12 entries with 114.75 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[900, 114.75, null], [1600, 114.75, null], [4200, 114.75, null], [8763, 114.75, null], [3300, 114.75, null], [600, 114.75, null], [1300, 114.75, null], [1837, 114.75, null], [4217, 114.75, null], [5400, 114.75, null], [3283, 114.75, null], [600, 114.75, null]],
            [28.69, 51.0, 133.88, 279.32, 105.19, 19.13, 41.44, 58.55, 134.42, 172.13, 104.65, 19.13],
            1147.53,
            218.03,
            1365.56,
            36000,
        ];

        yield 'old UtilTest: 12 entries with 114.75 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[900, 114.75, null], [1600, 114.75, null], [4200, 114.75, null], [8763, 114.75, null], [3300, 114.75, null], [600, 114.75, null], [1300, 114.75, null], [1837, 114.75, null], [4217, 114.75, null], [5400, 114.75, null], [3283, 114.75, null], [600, 114.75, null]],
            [28.69, 50.49, 134.26, 278.84, 105.57, 19.51, 41.31, 58.52, 134.26, 172.13, 104.42, 19.51],
            1147.51,
            218.03,
            1365.54,
            36000,
        ];

        yield 'old UtilTest: 12 entries with 114.75 per hour, grouped by price (classic)' => [
            'classic',
            'price',
            [19.0],
            [[900, 114.75, null], [1600, 114.75, null], [4200, 114.75, null], [8763, 114.75, null], [3300, 114.75, null], [600, 114.75, null], [1300, 114.75, null], [1837, 114.75, null], [4217, 114.75, null], [5400, 114.75, null], [3283, 114.75, null], [600, 114.75, null]],
            [1147.5],
            1147.5,
            218.03,
            1365.53,
            36000,
        ];

        yield 'old UtilTest: 12 entries with 114.75 per hour, grouped by price (decimal)' => [
            'decimal',
            'price',
            [19.0],
            [[900, 114.75, null], [1600, 114.75, null], [4200, 114.75, null], [8763, 114.75, null], [3300, 114.75, null], [600, 114.75, null], [1300, 114.75, null], [1837, 114.75, null], [4217, 114.75, null], [5400, 114.75, null], [3283, 114.75, null], [600, 114.75, null]],
            [1147.5],
            1147.5,
            218.03,
            1365.53,
            36000,
        ];

        yield 'old PriceInvoiceCalculatorTest: 293.27 per hour (classic)' => [
            'classic',
            'default',
            [19.0],
            [[400, 293.27, null], [1800, 293.27, null], [400, 293.27, null], [400, 293.27, null]],
            [32.59, 146.64, 32.59, 32.59],
            244.41,
            46.44,
            290.85,
            3000,
        ];

        yield 'old PriceInvoiceCalculatorTest: 293.27 per hour (decimal)' => [
            'decimal',
            'default',
            [19.0],
            [[400, 293.27, null], [1800, 293.27, null], [400, 293.27, null], [400, 293.27, null]],
            [32.26, 146.64, 32.26, 32.26],
            243.42,
            46.25,
            289.67,
            2988,
        ];

        yield 'old PriceInvoiceCalculatorTest: 293.27 per hour, grouped by price (classic)' => [
            'classic',
            'price',
            [19.0],
            [[400, 293.27, null], [1800, 293.27, null], [400, 293.27, null], [400, 293.27, null]],
            [244.39],
            244.39,
            46.43,
            290.82,
            3000,
        ];

        yield 'old PriceInvoiceCalculatorTest: 293.27 per hour, grouped by price (decimal)' => [
            'decimal',
            'price',
            [19.0],
            [[400, 293.27, null], [1800, 293.27, null], [400, 293.27, null], [400, 293.27, null]],
            [243.41],
            243.41,
            46.25,
            289.66,
            2988,
        ];
    }

    /**
     * @param array<float> $taxRates
     * @param array<array{int, ?float, ?float}> $entries
     * @param array<float> $expectedLines
     */
    #[DataProvider('getInvoiceData')]
    public function testInvoiceCalculation(string $mode, string $calculator, array $taxRates, array $entries, array $expectedLines, float $subtotal, float $tax, float $total, int $timeWorked): void
    {
        $rateCalculatorMode = $mode === 'decimal' ? new DecimalRateCalculator() : new ClassicRateCalculator();

        $customer = new Customer('foo');
        $customer->setCurrency('EUR');

        $taxes = [];
        foreach ($taxRates as $rate) {
            $taxes[] = new Tax(TaxType::STANDARD, $rate, 'vat', true, null);
        }
        $template = new InvoiceTemplate();
        $template->setTaxRates($taxes);

        $timesheets = [];
        $begin = new \DateTime('2026-11-01 08:00:00');
        foreach ($entries as [$duration, $hourlyRate, $fixedRate]) {
            $timesheet = new Timesheet();
            $timesheet->setBegin(clone $begin);
            $timesheet->setActivity(new Activity());
            $timesheet->setDuration($duration);
            if ($fixedRate !== null) {
                $timesheet->setFixedRate($fixedRate);
                $timesheet->setRate($fixedRate);
            } elseif ($hourlyRate !== null) {
                $timesheet->setHourlyRate($hourlyRate);
                // like the RateService would store it
                $timesheet->setRate($rateCalculatorMode->calculateRate($hourlyRate, $duration, 'EUR'));
            }
            $timesheets[] = $timesheet;
            $begin->modify('+1 hour');
        }

        $model = (new InvoiceModelFactoryFactory($this))->create($rateCalculatorMode)->createModel(new DebugFormatter(), $customer, $template, new InvoiceQuery());
        $model->addEntries($timesheets);

        $sut = $calculator === 'price' ? new PriceInvoiceCalculator() : new DefaultCalculator();
        $sut->setModel($model);

        $lines = [];
        foreach ($sut->getEntries() as $item) {
            $lines[] = $item->getRate();
        }

        self::assertSame($expectedLines, $lines);
        self::assertSame($subtotal, $sut->getSubtotal());
        self::assertSame($tax, $sut->getTax());
        self::assertSame($total, $sut->getTotal());
        self::assertSame($timeWorked, $sut->getTimeWorked());

        // the invoice must add up: lines => subtotal => tax rows => total
        self::assertSame($subtotal, round(array_sum($lines), 2));
        $taxRows = $sut->getTaxRows();
        self::assertCount(\count($taxRates), $taxRows);
        $taxSum = 0.0;
        foreach ($taxRows as $row) {
            self::assertSame($subtotal, $row->getBasePrice());
            $taxSum += $row->getAmount();
        }
        self::assertSame($tax, round($taxSum, 2));
        self::assertSame($total, round($subtotal + $tax, 2));
    }
}
