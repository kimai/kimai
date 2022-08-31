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

        self::assertNull($sut->getBudgetType());
        self::assertFalse($sut->isIncludeNoBudget());
        self::assertFalse($sut->isBudgetTypeMonthly());
        self::assertTrue($sut->isBudgetIndependent());
    }

    public function testSetterGetter()
    {
        $sut = new ProjectDateRangeQuery(new \DateTime(), new User());

        $date = new \DateTime('+1 year');
        $customer = new Customer('foo');

        $sut->setMonth($date);
        $sut->setCustomer($customer);
        $sut->setIncludeNoWork(false);

        self::assertEquals($date->getTimestamp(), $sut->getMonth()->getTimestamp());
        self::assertSame($customer, $sut->getCustomer());
        self::assertFalse($sut->isIncludeNoWork());

        $sut->setBudgetType('none');
        self::assertEquals('none', $sut->getBudgetType());
        self::assertTrue($sut->isIncludeNoBudget());
        self::assertFalse($sut->isBudgetTypeMonthly());

        $sut->setBudgetType('full');
        self::assertEquals('full', $sut->getBudgetType());
        self::assertFalse($sut->isBudgetTypeMonthly());
        self::assertFalse($sut->isIncludeNoBudget());
    }
}
