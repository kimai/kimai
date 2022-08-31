<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting\ProjectView;

use App\Entity\Customer;
use App\Entity\User;
use App\Reporting\ProjectView\ProjectViewQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Reporting\ProjectView\ProjectViewQuery
 */
class ProjectViewQueryTest extends TestCase
{
    public function testDefaults()
    {
        $user = new User();
        $date = new \DateTime();
        $sut = new ProjectViewQuery($date, $user);

        self::assertSame($date, $sut->getToday());
        self::assertSame($user, $sut->getUser());
        self::assertNull($sut->getCustomer());
        self::assertFalse($sut->isIncludeWithoutBudget());
        self::assertTrue($sut->isIncludeWithBudget());
        self::assertFalse($sut->isIncludeNoWork());
    }

    public function testSetterGetter()
    {
        $user = new User();
        $date = new \DateTime();
        $sut = new ProjectViewQuery($date, $user);

        $customer = new Customer('foo');

        $sut->setCustomer($customer);
        $sut->setIncludeNoWork(true);

        self::assertSame($customer, $sut->getCustomer());
        self::assertTrue($sut->isIncludeNoWork());

        $sut->setBudgetType(true);
        self::assertTrue($sut->isIncludeWithBudget());
        self::assertFalse($sut->isIncludeWithoutBudget());

        $sut->setBudgetType(false);
        self::assertFalse($sut->isIncludeWithBudget());
        self::assertTrue($sut->isIncludeWithoutBudget());

        $sut->setBudgetType(null);
        self::assertFalse($sut->isIncludeWithBudget());
        self::assertFalse($sut->isIncludeWithoutBudget());
    }
}
