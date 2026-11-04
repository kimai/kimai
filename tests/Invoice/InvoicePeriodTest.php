<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Invoice\InvoicePeriod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoicePeriod::class)]
class InvoicePeriodTest extends TestCase
{
    public function testGetters(): void
    {
        $start = new \DateTimeImmutable('2024-01-02 03:04:05');
        $end = new \DateTimeImmutable('2024-06-07 08:09:10');

        $sut = new InvoicePeriod($start, $end);

        self::assertSame($start, $sut->getStart());
        self::assertSame($end, $sut->getEnd());
    }
}
