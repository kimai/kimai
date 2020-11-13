<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Activity;
use App\Entity\ActivityRate;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\ActivityRate
 * @covers \App\Entity\Rate
 */
class ActivityRateTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new ActivityRate();
        self::assertNull($sut->getId());
        self::assertEquals(0.00, $sut->getRate());
        self::assertNull($sut->getInternalRate());
        self::assertNull($sut->getActivity());
        self::assertNull($sut->getUser());
        self::assertEquals(5, $sut->getScore());
        self::assertFalse($sut->isFixed());
    }

    public function testSetterAndGetter()
    {
        $sut = new ActivityRate();

        self::assertInstanceOf(ActivityRate::class, $sut->setIsFixed(true));
        self::assertTrue($sut->isFixed());

        self::assertInstanceOf(ActivityRate::class, $sut->setRate(12.34));
        self::assertEquals(12.34, $sut->getRate());

        self::assertInstanceOf(ActivityRate::class, $sut->setInternalRate(7.12));
        self::assertEquals(7.12, $sut->getInternalRate());
        $sut->setInternalRate(null);
        self::assertNull($sut->getInternalRate());

        $user = new User();
        $user->setAlias('foo');
        $user->setUsername('bar');
        self::assertInstanceOf(ActivityRate::class, $sut->setUser($user));
        self::assertSame($user, $sut->getUser());
        $sut->setUser(null);
        self::assertNull($sut->getUser());

        $entity = new Activity();
        $entity->setName('foo');
        self::assertInstanceOf(ActivityRate::class, $sut->setActivity($entity));
        self::assertSame($entity, $sut->getActivity());
    }
}
