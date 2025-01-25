<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Customer;
use App\Repository\Query\CustomerFormTypeQuery;

/**
 * @covers \App\Repository\Query\CustomerFormTypeQuery
 * @covers \App\Repository\Query\BaseFormTypeQuery
 */
class CustomerFormTypeQueryTest extends AbstractBaseFormTypeQueryTestCase
{
    public function testQuery(): void
    {
        $sut = new CustomerFormTypeQuery();

        $this->assertBaseQuery($sut);

        $customer = new Customer('foo');
        self::assertFalse($sut->isAllowCustomerPreselect());
        $sut->setAllowCustomerPreselect(true);
        self::assertTrue($sut->isAllowCustomerPreselect());
        self::assertNull($sut->getCustomerToIgnore());
        self::assertInstanceOf(CustomerFormTypeQuery::class, $sut->setCustomerToIgnore($customer));
        self::assertSame($customer, $sut->getCustomerToIgnore());
    }
}
