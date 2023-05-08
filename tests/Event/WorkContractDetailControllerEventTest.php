<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\WorkContractDetailControllerEvent;
use App\WorkingTime\Model\Year;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\WorkContractDetailControllerEvent
 */
class WorkContractDetailControllerEventTest extends TestCase
{
    public function testGetter(): void
    {
        $user = new User();
        $date = new \DateTime('2023-02-10');
        $year = new Year($date, $user);
        $sut = new WorkContractDetailControllerEvent($year);

        self::assertSame($year, $sut->getYear());
        self::assertEquals([], $sut->getController());

        $sut->addController('foo');
        $sut->addController('bar');
        self::assertEquals(['foo', 'bar'], $sut->getController());
    }
}
