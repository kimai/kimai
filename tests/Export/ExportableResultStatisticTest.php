<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Entity\Customer;
use App\Export\ExportableResultStatistic;
use App\Export\ExportableResultStatisticInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExportableResultStatistic::class)]
class ExportableResultStatisticTest extends TestCase
{
    public function testGetter(): void
    {
        $customer = new Customer('foo');

        $sut = new ExportableResultStatistic($customer, 3, 7200, 123.45);

        self::assertInstanceOf(ExportableResultStatisticInterface::class, $sut);
        self::assertSame($customer, $sut->getCustomer());
        self::assertSame(3, $sut->getCount());
        self::assertSame(7200, $sut->getDuration());
        self::assertSame(123.45, $sut->getRate());
    }
}
