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
use App\Repository\Query\InvoiceQuery;

/**
 * @covers \App\Repository\Query\InvoiceQuery
 */
class InvoiceQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new InvoiceQuery();

        $this->assertResultType($sut);
        $this->assertPage($sut);
        $this->assertPageSize($sut);
        $this->assertOrderBy($sut, 'begin');
        $this->assertOrder($sut, InvoiceQuery::ORDER_DESC);

        $this->assertUser($sut);
        $this->assertCustomer($sut);
        $this->assertProject($sut);
        $this->assertActivity($sut);
        $this->assertState($sut);
        $this->assertExported($sut);
        $this->assertMarkAsExported($sut);
    }

    protected function assertMarkAsExported(InvoiceQuery $sut)
    {
        $this->assertFalse($sut->isMarkAsExported());

        $sut->setMarkAsExported(true);
        $this->assertTrue($sut->isMarkAsExported());
    }

    protected function assertUser(InvoiceQuery $sut)
    {
        $this->assertNull($sut->getUser());

        $expected = new User();
        $expected->setUsername('foo-bar');
        $sut->setUser($expected);
        $this->assertEquals($expected, $sut->getUser());
    }

    protected function assertCustomer(InvoiceQuery $sut)
    {
        $this->assertNull($sut->getCustomer());

        $expected = new Customer();
        $expected->setName('foo-bar');
        $sut->setCustomer($expected);
        $this->assertEquals($expected, $sut->getCustomer());
    }

    protected function assertProject(InvoiceQuery $sut)
    {
        $this->assertNull($sut->getProject());

        $expected = new Project();
        $expected->setName('foo-bar');
        $sut->setProject($expected);
        $this->assertEquals($expected, $sut->getProject());
    }

    protected function assertActivity(InvoiceQuery $sut)
    {
        $this->assertNull($sut->getActivity());

        $expected = new Activity();
        $expected->setName('foo-bar');
        $sut->setActivity($expected);
        $this->assertEquals($expected, $sut->getActivity());
    }

    protected function assertState(InvoiceQuery $sut)
    {
        $this->assertEquals(InvoiceQuery::STATE_ALL, $sut->getState());

        $sut->setState(PHP_INT_MAX);
        $this->assertEquals(InvoiceQuery::STATE_ALL, $sut->getState());

        $sut->setState(InvoiceQuery::STATE_STOPPED);
        $this->assertEquals(InvoiceQuery::STATE_STOPPED, $sut->getState());

        $sut->setState(InvoiceQuery::STATE_RUNNING);
        $this->assertEquals(InvoiceQuery::STATE_RUNNING, $sut->getState());

        $sut->setState(InvoiceQuery::STATE_ALL);
        $this->assertEquals(InvoiceQuery::STATE_ALL, $sut->getState());
    }

    protected function assertExported(InvoiceQuery $sut)
    {
        $this->assertEquals(InvoiceQuery::STATE_ALL, $sut->getExported());

        $sut->setExported(PHP_INT_MAX);
        $this->assertEquals(InvoiceQuery::STATE_ALL, $sut->getExported());

        $sut->setExported(InvoiceQuery::STATE_EXPORTED);
        $this->assertEquals(InvoiceQuery::STATE_EXPORTED, $sut->getExported());

        $sut->setExported(InvoiceQuery::STATE_NOT_EXPORTED);
        $this->assertEquals(InvoiceQuery::STATE_NOT_EXPORTED, $sut->getExported());

        $sut->setExported(InvoiceQuery::STATE_ALL);
        $this->assertEquals(InvoiceQuery::STATE_ALL, $sut->getExported());
    }
}
