<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Repository\Query\InvoiceQuery;

/**
 * @covers \App\Repository\Query\InvoiceQuery
 * @covers \App\Repository\Query\TimesheetQuery
 */
class InvoiceQueryTest extends TimesheetQueryTest
{
    public function testQuery(): void
    {
        $sut = new InvoiceQuery();

        $this->assertPage($sut);
        $this->assertPageSize($sut);
        $this->assertOrderBy($sut, 'begin');
        $this->assertOrder($sut);

        $this->assertUser($sut);
        $this->assertCustomer($sut);
        $this->assertProject($sut);
        $this->assertActivity($sut);

        self::assertEquals(InvoiceQuery::STATE_STOPPED, $sut->getState());
        self::assertFalse($sut->isRunning());
        self::assertTrue($sut->isStopped());

        $this->assertExportedWith($sut, InvoiceQuery::STATE_NOT_EXPORTED);
        $this->assertModifiedAfter($sut);

        self::assertTrue($sut->getBillable());
        self::assertTrue($sut->isBillable());
        self::assertFalse($sut->isNotBillable());
        self::assertFalse($sut->isIgnoreBillable());

        self::assertTrue($sut->isBillable());
        self::assertFalse($sut->isNotBillable());
        $this->assertBillable($sut);
    }
}
