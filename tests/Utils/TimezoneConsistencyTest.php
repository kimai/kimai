<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use DateTimeImmutable;
use DateTimeZone;
use IntlDateFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TimezoneConsistencyTest extends TestCase
{
    #[DataProvider('getTimezoneCases')]
    public function testPhpAndIcuFormatTheSameTimestamp(
        string $timezone,
        string $timestamp,
        string $phpFormat,
        string $icuFormat
    ): void {
        $date = new DateTimeImmutable($timestamp, new DateTimeZone($timezone));
        $formatter = new IntlDateFormatter(
            'en',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            $timezone,
            IntlDateFormatter::GREGORIAN,
            $icuFormat
        );

        self::assertSame(
            $date->format($phpFormat),
            (string) $formatter->format($date),
            \sprintf('Timestamp formatting differs for %s at %s.', $timezone, $timestamp)
        );
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function getTimezoneCases(): array
    {
        return [
            ['Africa/Cairo', '2026-10-01 00:00:00', 'Y-m-d H:i P', 'yyyy-MM-dd HH:mm xxx'],
            ['Africa/Cairo', '2026-10-01 00:00:00', 'F Y', 'LLLL yyyy'],
            ['Africa/Cairo', '2026-10-01 00:00:00', 'D d', 'EE dd'],
            ['Africa/Cairo', '2026-10-01 00:00:00', 'h:i A', 'hh:mm a'],
            ['America/Mexico_City', '2026-07-01 12:00:00', 'Y-m-d H:i P', 'yyyy-MM-dd HH:mm xxx'],
            ['America/Mexico_City', '2026-07-01 12:00:00', 'F Y', 'LLLL yyyy'],
            ['America/Mexico_City', '2026-07-01 12:00:00', 'D d', 'EE dd'],
            ['America/Mexico_City', '2026-07-01 12:00:00', 'h:i A', 'hh:mm a'],
            ['Europe/Zurich', '2026-10-01 00:00:00', 'Y-m-d H:i P', 'yyyy-MM-dd HH:mm xxx'],
            ['Europe/Zurich', '2026-10-01 00:00:00', 'F Y', 'LLLL yyyy'],
            ['Europe/Zurich', '2026-10-01 00:00:00', 'D d', 'EE dd'],
            ['Europe/Zurich', '2026-10-01 00:00:00', 'h:i A', 'hh:mm a'],
        ];
    }

    public function testPhpAndIcuFormatMonthPickerLabelsTheSame(): void
    {
        $timezone = 'Africa/Cairo';
        $formatter = new IntlDateFormatter(
            'en',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            $timezone,
            IntlDateFormatter::GREGORIAN,
            'LLLL yyyy'
        );

        for ($month = 1; $month <= 12; ++$month) {
            $date = new DateTimeImmutable(
                \sprintf('2026-%02d-01 00:00:00', $month),
                new DateTimeZone($timezone)
            );

            self::assertSame(
                $date->format('F Y'),
                (string) $formatter->format($date),
                \sprintf('Month label formatting differs for %s.', $date->format('Y-m-d'))
            );
        }
    }
}
