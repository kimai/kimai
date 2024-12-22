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
        $this->assertOrder($sut);

        $this->assertUser($sut);
        $this->assertCustomer($sut);
        $this->assertProject($sut);
        $this->assertActivity($sut);
        $this->assertStateWith($sut, ExportQuery::STATE_STOPPED);
        $this->assertExportedWith($sut, ExportQuery::STATE_NOT_EXPORTED);
        $this->assertRenderer($sut);
        $this->assertMarkAsExported($sut);
    }

    public function assertMarkAsExported(ExportQuery $sut): void
    {
        self::assertFalse($sut->isMarkAsExported());

        $sut->setMarkAsExported(true);
        self::assertTrue($sut->isMarkAsExported());
    }

    public function assertRenderer(ExportQuery $sut): void
    {
        self::assertNull($sut->getRenderer());

        $exportTypes = ['html', 'csv', 'pdf', 'xlsx', 'ods'];

        foreach ($exportTypes as $type) {
            $sut->setRenderer($type);
            self::assertEquals($type, $sut->getRenderer());
        }
    }
}
