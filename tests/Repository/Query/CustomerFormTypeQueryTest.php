<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Customer;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\Query\CustomerFormTypeQuery;

/**
 * @covers \App\Repository\Query\CustomerFormTypeQuery
 */
class CustomerFormTypeQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new CustomerFormTypeQuery();

        self::assertEmpty($sut->getTeams());
        self::assertInstanceOf(CustomerFormTypeQuery::class, $sut->addTeam(new Team()));
        self::assertCount(1, $sut->getTeams());

        $customer = new Customer();
        self::assertNull($sut->getCustomer());
        self::assertInstanceOf(CustomerFormTypeQuery::class, $sut->setCustomer($customer));
        self::assertSame($customer, $sut->getCustomer());

        $customer = new Customer();
        self::assertNull($sut->getCustomerToIgnore());
        self::assertInstanceOf(CustomerFormTypeQuery::class, $sut->setCustomerToIgnore($customer));
        self::assertSame($customer, $sut->getCustomerToIgnore());

        $user = new User();
        self::assertNull($sut->getUser());
        self::assertInstanceOf(CustomerFormTypeQuery::class, $sut->setUser($user));
        self::assertSame($user, $sut->getUser());
    }
}
