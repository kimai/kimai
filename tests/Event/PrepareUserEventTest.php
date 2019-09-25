<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\PrepareUserEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\PrepareUserEvent
 */
class PrepareUserEventTest extends TestCase
{
    public function testGetterAndSetter()
    {
        $user = new User();
        $sut = new PrepareUserEvent($user);
        $this->assertEquals(PrepareUserEvent::class, PrepareUserEvent::PREPARE);
        $this->assertSame($user, $sut->getUser());
    }
}
