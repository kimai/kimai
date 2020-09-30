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
        $this->assertModifiedAfter($sut);

        self::assertNull($sut->getBillable());
        self::assertFalse($sut->isBillable());
        self::assertFalse($sut->isNotBillable());
        self::assertTrue($sut->isIgnoreBillable());

        self::assertFalse($sut->isBillable());
        self::assertFalse($sut->isNotBillable());
        $this->assertBillable($sut);

        $this->assertResetByFormError(new TimesheetQuery(), 'begin', 'DESC');
    }

    protected function assertUser(TimesheetQuery $sut)
    {
        self::assertNull($sut->getUser());

        $expected = new User();
        $expected->setUsername('foo-bar');
        self::assertInstanceOf(TimesheetQuery::class, $sut->setUser($expected));
        self::assertEquals($expected, $sut->getUser());
    }

    protected function assertUsers(TimesheetQuery $sut)
    {
        self::assertEmpty($sut->getUsers());

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

        self::assertCount(2, $sut->getUsers());
    }

    protected function assertState(TimesheetQuery $sut)
    {
        self::assertEquals(TimesheetQuery::STATE_ALL, $sut->getState());
        self::assertFalse($sut->isRunning());
        self::assertFalse($sut->isStopped());

        self::assertInstanceOf(TimesheetQuery::class, $sut->setState(PHP_INT_MAX));
        self::assertEquals(TimesheetQuery::STATE_ALL, $sut->getState());

        $sut->setState(TimesheetQuery::STATE_STOPPED);
        self::assertEquals(TimesheetQuery::STATE_STOPPED, $sut->getState());
        self::assertFalse($sut->isRunning());
        self::assertTrue($sut->isStopped());

        $sut->setState(TimesheetQuery::STATE_RUNNING);
        self::assertEquals(TimesheetQuery::STATE_RUNNING, $sut->getState());
        self::assertTrue($sut->isRunning());
        self::assertFalse($sut->isStopped());

        $sut->setState(TimesheetQuery::STATE_ALL);
        self::assertEquals(TimesheetQuery::STATE_ALL, $sut->getState());
    }

    protected function assertExported(TimesheetQuery $sut)
    {
        self::assertEquals(TimesheetQuery::STATE_ALL, $sut->getExported());
        self::assertFalse($sut->isExported());
        self::assertFalse($sut->isNotExported());

        self::assertInstanceOf(TimesheetQuery::class, $sut->setExported(PHP_INT_MAX));
        self::assertEquals(TimesheetQuery::STATE_ALL, $sut->getExported());

        $sut->setExported(TimesheetQuery::STATE_EXPORTED);
        self::assertEquals(TimesheetQuery::STATE_EXPORTED, $sut->getExported());
        self::assertTrue($sut->isExported());
        self::assertFalse($sut->isNotExported());

        $sut->setExported(TimesheetQuery::STATE_NOT_EXPORTED);
        self::assertEquals(TimesheetQuery::STATE_NOT_EXPORTED, $sut->getExported());
        self::assertFalse($sut->isExported());
        self::assertTrue($sut->isNotExported());

        $sut->setExported(TimesheetQuery::STATE_ALL);
        self::assertEquals(TimesheetQuery::STATE_ALL, $sut->getExported());

        $sut->setExported(2);
        self::assertEquals(TimesheetQuery::STATE_ALL, $sut->getExported());
    }

    protected function assertModifiedAfter(TimesheetQuery $sut)
    {
        self::assertNull($sut->getModifiedAfter());
        $date = new \DateTime('-3 hours');

        self::assertInstanceOf(TimesheetQuery::class, $sut->setModifiedAfter($date));
        self::assertNotNull($sut->getModifiedAfter()); // just here to fix a PHPStan issue
        self::assertSame($date, $sut->getModifiedAfter());
    }

    protected function assertBillable(TimesheetQuery $sut)
    {
        $sut->setBillable(null);
        self::assertNull($sut->getBillable());
        self::assertFalse($sut->isBillable());
        self::assertFalse($sut->isNotBillable());
        self::assertTrue($sut->isIgnoreBillable());

        $sut->setBillable(true);
        self::assertTrue($sut->getBillable());
        self::assertTrue($sut->isBillable());
        self::assertFalse($sut->isNotBillable());
        self::assertFalse($sut->isIgnoreBillable());

        $sut->setBillable(false);
        self::assertFalse($sut->getBillable());
        self::assertFalse($sut->isBillable());
        self::assertTrue($sut->isNotBillable());
        self::assertFalse($sut->isIgnoreBillable());
    }
}
