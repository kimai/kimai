<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Project;
use App\Entity\ProjectRate;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\ProjectRate
 * @covers \App\Entity\Rate
 */
class ProjectRateTest extends TestCase
{
    public function testDefaultValues()
    {
        $sut = new ProjectRate();
        self::assertNull($sut->getId());
        self::assertEquals(0.00, $sut->getRate());
        self::assertNull($sut->getInternalRate());
        self::assertNull($sut->getProject());
        self::assertNull($sut->getUser());
        self::assertEquals(3, $sut->getScore());
        self::assertFalse($sut->isFixed());
    }

    public function testSetterAndGetter()
    {
        $sut = new ProjectRate();

        self::assertInstanceOf(ProjectRate::class, $sut->setIsFixed(true));
        self::assertTrue($sut->isFixed());

        self::assertInstanceOf(ProjectRate::class, $sut->setRate(12.34));
        self::assertEquals(12.34, $sut->getRate());

        self::assertInstanceOf(ProjectRate::class, $sut->setInternalRate(7.12));
        self::assertEquals(7.12, $sut->getInternalRate());
        $sut->setInternalRate(null);
        self::assertNull($sut->getInternalRate());

        $user = new User();
        $user->setAlias('foo');
        $user->setUsername('bar');
        self::assertInstanceOf(ProjectRate::class, $sut->setUser($user));
        self::assertSame($user, $sut->getUser());
        $sut->setUser(null);
        self::assertNull($sut->getUser());

        $entity = new Project();
        $entity->setName('foo');
        self::assertInstanceOf(ProjectRate::class, $sut->setProject($entity));
        self::assertSame($entity, $sut->getProject());
    }
}
