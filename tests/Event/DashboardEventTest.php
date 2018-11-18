<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\Event\DashboardEvent;
use App\Model\DashboardSection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\DashboardEvent
 */
class DashboardEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = new DashboardEvent($user);

        $this->assertEquals($user, $sut->getUser());
        $this->assertEquals([], $sut->getSections());

        $section = new DashboardSection('foo');
        $sut->addSection($section);

        $this->assertEquals([$section], $sut->getSections());
    }
}
