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
 * @covers \App\Reporting\DateByUser
 */
abstract class AbstractDateByUserTest extends TestCase
{
    abstract protected function createSut(): DateByUser;

    public function testEmptyObject()
    {
        $sut = $this->createSut();
        self::assertNull($sut->getDate());
        self::assertNull($sut->getUser());
    }

    public function testSetter()
    {
        $date = new \DateTime('2019-05-27');
        $user = new User();
        $user->setAlias('sdfsdfdsdf');

        $sut = $this->createSut();
        self::assertInstanceOf(DateByUser::class, $sut->setDate($date));
        self::assertInstanceOf(DateByUser::class, $sut->setUser($user));

        self::assertSame($date, $sut->getDate());
        self::assertSame($user, $sut->getUser());
    }
}
