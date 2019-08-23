<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\Query\ExportQuery;

/**
 * @covers \App\Repository\Query\ExportQuery
 */
class ExportQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new ExportQuery();

        $this->assertResultType($sut);
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

    protected function assertUser(ExportQuery $sut)
    {
        $this->assertNull($sut->getUser());

        $expected = new User();
        $expected->setUsername('foo-bar');
        $sut->setUser($expected);
        $this->assertEquals($expected, $sut->getUser());
    }

    protected function assertCustomer(ExportQuery $sut)
    {
        $this->assertNull($sut->getCustomer());

        $expected = new Customer();
        $expected->setName('foo-bar');
        $sut->setCustomer($expected);
        $this->assertEquals($expected, $sut->getCustomer());
    }

    protected function assertProject(ExportQuery $sut)
    {
        $this->assertNull($sut->getProject());

        $expected = new Project();
        $expected->setName('foo-bar');
        $sut->setProject($expected);
        $this->assertEquals($expected, $sut->getProject());
    }

    protected function assertActivity(ExportQuery $sut)
    {
        $this->assertNull($sut->getActivity());

        $expected = new Activity();
        $expected->setName('foo-bar');
        $sut->setActivity($expected);
        $this->assertEquals($expected, $sut->getActivity());
    }

    protected function assertState(ExportQuery $sut)
    {
        $this->assertEquals(ExportQuery::STATE_ALL, $sut->getState());

        $sut->setState(PHP_INT_MAX);
        $this->assertEquals(ExportQuery::STATE_ALL, $sut->getState());

        $sut->setState(ExportQuery::STATE_STOPPED);
        $this->assertEquals(ExportQuery::STATE_STOPPED, $sut->getState());

        $sut->setState(ExportQuery::STATE_RUNNING);
        $this->assertEquals(ExportQuery::STATE_RUNNING, $sut->getState());

        $sut->setState(ExportQuery::STATE_ALL);
        $this->assertEquals(ExportQuery::STATE_ALL, $sut->getState());
    }

    protected function assertExported(ExportQuery $sut)
    {
        $this->assertEquals(ExportQuery::STATE_ALL, $sut->getExported());

        $sut->setExported(PHP_INT_MAX);
        $this->assertEquals(ExportQuery::STATE_ALL, $sut->getExported());

        $sut->setExported(ExportQuery::STATE_EXPORTED);
        $this->assertEquals(ExportQuery::STATE_EXPORTED, $sut->getExported());

        $sut->setExported(ExportQuery::STATE_NOT_EXPORTED);
        $this->assertEquals(ExportQuery::STATE_NOT_EXPORTED, $sut->getExported());

        $sut->setExported(ExportQuery::STATE_ALL);
        $this->assertEquals(ExportQuery::STATE_ALL, $sut->getExported());
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
