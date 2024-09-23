<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Mode;

use App\Entity\User;
use App\WorkingTime\Calculator\WorkingTimeCalculatorNone;
use App\WorkingTime\Mode\WorkingTimeModeNone;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Mode\WorkingTimeModeNone
 */
class WorkingTimeModeNoneTest extends TestCase
{
    public function testDefaults(): void
    {
        $sut = new WorkingTimeModeNone();
        $this->assertEquals('none', $sut->getId());
        $this->assertEquals(0, $sut->getOrder());
        $this->assertEquals('', $sut->getName());
        $this->assertInstanceOf(WorkingTimeCalculatorNone::class, $sut->getCalculator(new User()));
    }
}
