<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\WorkingTime\Mode;

use App\Entity\User;
use App\WorkingTime\Mode\WorkingTimeModeDay;
use App\WorkingTime\Mode\WorkingTimeModeFactory;
use App\WorkingTime\Mode\WorkingTimeModeNone;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\WorkingTime\Mode\WorkingTimeModeFactory
 */
class WorkingTimeModeFactoryTest extends TestCase
{
    public function testDefaults(): void
    {
        $none = new WorkingTimeModeNone();
        $day = new WorkingTimeModeDay();
        $modes = [$none, $day];
        $sut = new WorkingTimeModeFactory($modes);
        $this->assertEquals($modes, $sut->getAll());
        $this->assertSame($none, $sut->getMode('none'));
        $this->assertSame($day, $sut->getMode('day'));

        $user = new User();
        $user->setWorkContractMode('day');
        $this->assertSame($day, $sut->getModeForUser($user));
    }

    public function testException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown working contract mode: foo');

        $sut = new WorkingTimeModeFactory([]);
        $sut->getMode('foo');
    }
}
