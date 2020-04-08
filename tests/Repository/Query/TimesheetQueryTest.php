<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository\Query;

use App\Entity\User;
use App\Repository\Query\TimesheetQuery;

/**
 * @covers \App\Repository\Query\TimesheetQuery
 * @covers \App\Repository\Query\BaseQuery
 */
class TimesheetQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new TimesheetQuery();

        $this->assertPage($sut);
        $this->assertPageSize($sut);
        $this->assertOrderBy($sut, 'begin');
        $this->assertOrder($sut, TimesheetQuery::ORDER_DESC);

        $this->assertUser($sut);
        $this->assertUsers($sut);
        $this->assertCustomer($sut);
        $this->assertProject($sut);
        $this->assertActivity($sut);
        $this->assertState($sut);
        $this->assertExported($sut);
        $this->assertSearchTerm($sut);

        $this->assertResetByFormError(new TimesheetQuery(), 'begin', 'DESC');
    }

    protected function assertUser(TimesheetQuery $sut)
    {
        $this->assertNull($sut->getUser());

        $expected = new User();
        $expected->setUsername('foo-bar');
        $sut->setUser($expected);
        $this->assertEquals($expected, $sut->getUser());
    }

    protected function assertUsers(TimesheetQuery $sut)
    {
        $this->assertEmpty($sut->getUsers());

        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getId')->willReturn(1);
        $sut->addUser($user);

        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getId')->willReturn(1);
        $sut->addUser($user);

        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getId')->willReturn(13);
        $sut->addUser($user);

        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getId')->willReturn(27);
        $sut->addUser($user);
        $sut->removeUser($user);

        $this->assertCount(2, $sut->getUsers());
    }

    protected function assertState(TimesheetQuery $sut)
    {
        $this->assertEquals(TimesheetQuery::STATE_ALL, $sut->getState());

        $sut->setState(PHP_INT_MAX);
        $this->assertEquals(TimesheetQuery::STATE_ALL, $sut->getState());

        $sut->setState(TimesheetQuery::STATE_STOPPED);
        $this->assertEquals(TimesheetQuery::STATE_STOPPED, $sut->getState());

        $sut->setState(TimesheetQuery::STATE_RUNNING);
        $this->assertEquals(TimesheetQuery::STATE_RUNNING, $sut->getState());

        $sut->setState(TimesheetQuery::STATE_ALL);
        $this->assertEquals(TimesheetQuery::STATE_ALL, $sut->getState());
    }

    protected function assertExported(TimesheetQuery $sut)
    {
        $this->assertEquals(TimesheetQuery::STATE_ALL, $sut->getExported());

        $sut->setExported(PHP_INT_MAX);
        $this->assertEquals(TimesheetQuery::STATE_ALL, $sut->getExported());

        $sut->setExported(TimesheetQuery::STATE_EXPORTED);
        $this->assertEquals(TimesheetQuery::STATE_EXPORTED, $sut->getExported());

        $sut->setExported(TimesheetQuery::STATE_NOT_EXPORTED);
        $this->assertEquals(TimesheetQuery::STATE_NOT_EXPORTED, $sut->getExported());

        $sut->setExported(TimesheetQuery::STATE_ALL);
        $this->assertEquals(TimesheetQuery::STATE_ALL, $sut->getExported());

        $sut->setExported('02');
        $this->assertEquals(TimesheetQuery::STATE_ALL, $sut->getExported());
    }
}
