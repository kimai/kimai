<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Repository\Query\ExportQuery;

/**
 * @covers \App\Repository\Query\ExportQuery
 * @covers \App\Repository\Query\TimesheetQuery
 */
class ExportQueryTest extends TimesheetQueryTest
{
    public function testQuery()
    {
        $sut = new ExportQuery();

        $this->assertPage($sut);
        $this->assertPageSize($sut);
        $this->assertOrderBy($sut, 'begin');
        $this->assertOrder($sut, ExportQuery::ORDER_DESC);

        $this->assertUser($sut);
        $this->assertCustomer($sut);
        $this->assertProject($sut);
        $this->assertActivity($sut);
        $this->assertState($sut);
        $this->assertExported($sut);
        $this->assertType($sut);
        $this->assertMarkAsExported($sut);
    }

    protected function assertMarkAsExported(ExportQuery $sut)
    {
        $this->assertFalse($sut->isMarkAsExported());

        $sut->setMarkAsExported(true);
        $this->assertTrue($sut->isMarkAsExported());
    }

    protected function assertType(ExportQuery $sut)
    {
        $this->assertNull($sut->getType());

        $exportTypes = ['html', 'csv', 'pdf', 'xlsx', 'ods'];

        foreach ($exportTypes as $type) {
            $sut->setType($type);
            $this->assertEquals($type, $sut->getType());
        }
    }
}
