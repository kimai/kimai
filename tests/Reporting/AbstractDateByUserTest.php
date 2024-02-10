<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Reporting;

use App\Entity\User;
use App\Reporting\DateByUser;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Reporting\AbstractUserList
 * @covers \App\Reporting\DateByUser
 */
abstract class AbstractDateByUserTest extends TestCase
{
    abstract protected function createSut(): DateByUser;

    public function testEmptyObject(): void
    {
        $sut = $this->createSut();
        self::assertNull($sut->getDate());
        self::assertNull($sut->getUser());
        self::assertEquals('duration', $sut->getSumType());
        self::assertFalse($sut->isDecimal());
    }

    public function testSetter(): void
    {
        $date = new \DateTime('2019-05-27');
        $user = new User();
        $user->setAlias('sdfsdfdsdf');

        $sut = $this->createSut();
        $sut->setDate($date);
        $sut->setUser($user);

        self::assertSame($date, $sut->getDate());
        self::assertSame($user, $sut->getUser());

        $sut->setSumType('rate');
        self::assertEquals('rate', $sut->getSumType());

        $sut->setSumType('internalRate');
        self::assertEquals('internalRate', $sut->getSumType());

        $sut->setSumType('duration');
        self::assertEquals('duration', $sut->getSumType());

        $sut->setDecimal(true);
        self::assertTrue($sut->isDecimal());

        $sut->setDecimal(false);
        self::assertFalse($sut->isDecimal());
    }

    public function testInvalidSumType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $sut = $this->createSut();
        $sut->setSumType('DURation');
    }
}
