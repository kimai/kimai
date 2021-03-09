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
        self::assertFalse($sut->isIncludeNoBudget());
        self::assertFalse($sut->isIncludeNoWork());
    }

    public function testSetterGetter()
    {
        $user = new User();
        $date = new \DateTime();
        $sut = new ProjectViewQuery($date, $user);

        $customer = new Customer();

        $sut->setCustomer($customer);
        $sut->setIncludeNoBudget(true);
        $sut->setIncludeNoWork(true);

        self::assertSame($customer, $sut->getCustomer());
        self::assertTrue($sut->isIncludeNoBudget());
        self::assertTrue($sut->isIncludeNoWork());
    }
}
