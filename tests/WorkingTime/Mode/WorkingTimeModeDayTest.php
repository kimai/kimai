<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Mode;

use App\Entity\User;
use App\WorkingTime\Calculator\WorkingTimeCalculatorDay;
use App\WorkingTime\Mode\WorkingTimeModeDay;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Mode\WorkingTimeModeDay
 */
class WorkingTimeModeDayTest extends TestCase
{
    public function testDefaults(): void
    {
        $user = new User();
        $user->setWorkContractMode('day');
        $sut = new WorkingTimeModeDay();
        $this->assertEquals('day', $sut->getId());
        $this->assertEquals(10, $sut->getOrder());
        $this->assertEquals('hours_per_day', $sut->getName());
        $this->assertInstanceOf(WorkingTimeCalculatorDay::class, $sut->getCalculator($user));
        $fields = $sut->getFormFields();
        $this->assertCount(7, $fields);
    }
}
