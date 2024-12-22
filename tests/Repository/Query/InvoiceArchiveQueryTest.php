<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Repository\Query\BaseQuery;
use App\Repository\Query\InvoiceArchiveQuery;

/**
 * @covers \App\Repository\Query\InvoiceArchiveQuery
 */
class InvoiceArchiveQueryTest extends BaseQueryTest
{
    public function testQuery(): void
    {
        $sut = new InvoiceArchiveQuery();
        self::assertFalse($sut->hasStatus());
        $this->assertBaseQuery($sut, 'date', BaseQuery::ORDER_DESC);
        $this->assertDateRangeTrait($sut);

        self::assertIsArray($sut->getCustomers());
        self::assertEmpty($sut->getCustomers());
        self::assertFalse($sut->hasCustomers());

        $sut->addCustomer(new Customer('foo'));
        $sut->setCustomers([new Customer('foo')]);
        self::assertCount(2, $sut->getCustomers());
        self::assertTrue($sut->hasCustomers());

        $sut->addStatus(Invoice::STATUS_PAID);
        $sut->setStatus([Invoice::STATUS_PENDING]);
        self::assertTrue($sut->hasStatus());
        self::assertEquals([Invoice::STATUS_PAID, Invoice::STATUS_PENDING], $sut->getStatus());

        $this->assertResetByFormError(new InvoiceArchiveQuery(), 'date', BaseQuery::ORDER_DESC);
    }
}
