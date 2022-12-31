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
    public function testQuery(): void
    {
        $sut = new ExportQuery();

        $this->assertPage($sut);
        $this->assertPageSize($sut);
        $this->assertOrderBy($sut, 'begin');
        $this->assertOrder($sut, ExportQuery::ORDER_ASC);

        $this->assertUser($sut);
        $this->assertCustomer($sut);
        $this->assertProject($sut);
        $this->assertActivity($sut);
        $this->assertStateWith($sut, ExportQuery::STATE_STOPPED);
        $this->assertExportedWith($sut, ExportQuery::STATE_NOT_EXPORTED);
        $this->assertRenderer($sut);
        $this->assertMarkAsExported($sut);
    }

    protected function assertMarkAsExported(ExportQuery $sut)
    {
        $this->assertTrue($sut->isMarkAsExported());

        $sut->setMarkAsExported(false);
        $this->assertFalse($sut->isMarkAsExported());
    }

    protected function assertRenderer(ExportQuery $sut)
    {
        $this->assertNull($sut->getRenderer());

        $exportTypes = ['html', 'csv', 'pdf', 'xlsx', 'ods'];

        foreach ($exportTypes as $type) {
            $sut->setRenderer($type);
            $this->assertEquals($type, $sut->getRenderer());
        }
    }
}
