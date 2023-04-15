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
    public function testQuery(): void
    {
        $sut = new TimesheetQuery();

        $this->assertPage($sut);
        $this->assertPageSize($sut);
        $this->assertOrderBy($sut, 'begin');
        $this->assertOrder($sut, TimesheetQuery::ORDER_DESC);
        $this->assertDateRangeTrait($sut);

        $this->assertUser($sut);
        $this->assertUsers($sut);
        $this->assertCustomer($sut);
        $this->assertProject($sut);
        $this->assertActivity($sut);
        $this->assertState($sut);
        $this->assertExported($sut);
        $this->assertSearchTerm($sut);
        $this->assertModifiedAfter($sut);
        $this->assertMaxResults($sut);

        self::assertNull($sut->getBillable());
        self::assertFalse($sut->isBillable());
        self::assertFalse($sut->isNotBillable());
        self::assertTrue($sut->isIgnoreBillable());

        self::assertFalse($sut->isBillable());
        self::assertFalse($sut->isNotBillable());
        $this->assertBillable($sut);

        $this->assertResetByFormError(new TimesheetQuery(), 'begin', 'DESC');
    }

    protected function assertMaxResults(TimesheetQuery $sut): void
    {
        self::assertNull($sut->getMaxResults());
        $sut->setMaxResults(999);
        self::assertEquals(999, $sut->getMaxResults());
    }

    protected function assertUser(TimesheetQuery $sut): void
    {
        self::assertNull($sut->getUser());
        self::assertFalse($sut->hasUsers());

        $expected = new User();
        $expected->setUserIdentifier('foo-bar');
        $sut->setUser($expected);
        self::assertEquals($expected, $sut->getUser());
        self::assertTrue($sut->hasUsers());

        $sut->setUser(null);
        self::assertFalse($sut->hasUsers());
    }

    protected function assertUsers(TimesheetQuery $sut): void
    {
        self::assertEmpty($sut->getUsers());
        self::assertFalse($sut->hasUsers());

        $user = $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock();
        $user->method('getId')->willReturn(1);
        $sut->addUser($user);
        self::assertTrue($sut->hasUsers());

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

    protected function assertState(TimesheetQuery $sut): void
    {
        self::assertEquals(TimesheetQuery::STATE_ALL, $sut->getState());
        self::assertFalse($sut->isRunning());
        self::assertFalse($sut->isStopped());

        $this->assertStateWith($sut, TimesheetQuery::STATE_ALL);
    }

    protected function assertStateWith(TimesheetQuery $sut, int $defaultState): void
    {
        self::assertInstanceOf(TimesheetQuery::class, $sut->setState(PHP_INT_MAX));
        self::assertEquals($defaultState, $sut->getState());

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

    protected function assertExported(TimesheetQuery $sut): void
    {
        self::assertEquals(TimesheetQuery::STATE_ALL, $sut->getExported());
        self::assertFalse($sut->isExported());
        self::assertFalse($sut->isNotExported());

        $this->assertExportedWith($sut, TimesheetQuery::STATE_ALL);
    }

    protected function assertExportedWith(TimesheetQuery $sut, int $defaultState): void
    {
        self::assertEquals($defaultState, $sut->getExported());

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

        $catched = false;
        try {
            $sut->setExported(2);
        } catch (\InvalidArgumentException $exception) {
            $catched = true;
            $this->assertEquals('Unknown export state given', $exception->getMessage());
        }

        self::assertTrue($catched);
    }

    protected function assertModifiedAfter(TimesheetQuery $sut): void
    {
        self::assertNull($sut->getModifiedAfter());
        $date = new \DateTime('-3 hours');

        self::assertInstanceOf(TimesheetQuery::class, $sut->setModifiedAfter($date));
        self::assertNotNull($sut->getModifiedAfter()); // just here to fix a PHPStan issue
        self::assertSame($date, $sut->getModifiedAfter());
    }

    protected function assertBillable(TimesheetQuery $sut): void
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
