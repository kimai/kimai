<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Model;

use App\Entity\User;
use App\Entity\WorkingTime;
use App\WorkingTime\Model\Day;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Model\Day
 */
class DayTest extends TestCase
{
    public function testDefaults(): void
    {
        $sut = new Day(new \DateTimeImmutable());
        self::assertNull($sut->getWorkingTime());
    }

    public function testSetter(): void
    {
        $sut = new Day(new \DateTimeImmutable());
        $workingTime = new WorkingTime(new User(), new \DateTimeImmutable());
        $sut->setWorkingTime($workingTime);
        self::assertSame($workingTime, $sut->getWorkingTime());
    }
}
