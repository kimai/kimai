<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\DashboardEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\DashboardEvent
 */
class DashboardEventTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = new DashboardEvent($user);

        $this->assertEquals($user, $sut->getUser());

        $sut->addWidget('test');
        $sut->addWidget('test2', 5);
        $sut->addWidget('test3', 45);
        $sut->addWidget('test4', 10);
        $sut->addWidget('test5', 5);

        $this->assertSame([0 => 'test', 5 => 'test2', 6 => 'test5', 10 => 'test4', 45 => 'test3'], $sut->getWidgets());
    }
}
