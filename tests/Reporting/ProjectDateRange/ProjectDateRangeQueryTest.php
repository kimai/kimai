<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\ProjectDateRange;

use App\Entity\Customer;
use App\Entity\User;
use App\Reporting\ProjectDateRange\ProjectDateRangeQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Reporting\ProjectDateRange\ProjectDateRangeQuery
 */
class ProjectDateRangeQueryTest extends TestCase
{
    public function testDefaults()
    {
        $user = new User();
        $date = new \DateTime();
        $sut = new ProjectDateRangeQuery($date, $user);

        self::assertEquals($date->getTimestamp(), $sut->getMonth()->getTimestamp());
        self::assertSame($user, $sut->getUser());
        self::assertNull($sut->getCustomer());
        self::assertFalse($sut->isIncludeNoWork());
        self::assertFalse($sut->isIncludeNoBudget());
    }

    public function testSetterGetter()
    {
        $sut = new ProjectDateRangeQuery(new \DateTime(), new User());

        $date = new \DateTime('+1 year');
        $customer = new Customer();

        $sut->setMonth($date);
        $sut->setCustomer($customer);
        $sut->setIncludeNoBudget(true);
        $sut->setIncludeNoWork(true);

        self::assertEquals($date->getTimestamp(), $sut->getMonth()->getTimestamp());
        self::assertSame($customer, $sut->getCustomer());
        self::assertTrue($sut->isIncludeNoWork());
        self::assertTrue($sut->isIncludeNoBudget());
    }
}
