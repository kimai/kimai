<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Model\DateRange;
use App\Form\Type\DateRangeType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateRangeType::class)]
class DateRangeTypeTest extends TestCase
{
    public function testFormatQueryString(): void
    {
        self::assertEquals('2026-01-01 - 2026-01-31', DateRangeType::formatQueryString(new \DateTime('2026-01-01'), new \DateTime('2026-01-31')));
        self::assertEquals('2025-12-31 - 2026-02-01', DateRangeType::formatQueryString(new \DateTimeImmutable('2025-12-31 23:59:59'), new \DateTimeImmutable('2026-02-01 00:00:00')));
    }

    public function testFormatQueryStringIgnoresTimezone(): void
    {
        $begin = new \DateTime('2026-03-01 00:30:00', new \DateTimeZone('Europe/Berlin'));
        $end = new \DateTime('2026-03-31 23:30:00', new \DateTimeZone('America/New_York'));

        self::assertEquals('2026-03-01 - 2026-03-31', DateRangeType::formatQueryString($begin, $end));
    }

    public function testToQueryString(): void
    {
        $range = new DateRange();
        $range->setBegin(new \DateTime('2026-01-01 12:00:00'));
        $range->setEnd(new \DateTime('2026-01-31 08:00:00'));

        self::assertEquals('2026-01-01 - 2026-01-31', DateRangeType::toQueryString($range));
    }

    public function testToQueryStringWithIncompleteRange(): void
    {
        self::assertEquals('', DateRangeType::toQueryString(new DateRange()));
        self::assertEquals('', DateRangeType::toQueryString((new DateRange())->setBegin(new \DateTime('2026-01-01'))));
        self::assertEquals('', DateRangeType::toQueryString((new DateRange())->setEnd(new \DateTime('2026-01-31'))));
    }
}
