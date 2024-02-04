<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\UserCreatePostEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\AbstractUserEvent
 * @covers \App\Event\UserCreatePostEvent
 */
class UserCreatePostEventTest extends TestCase
{
    public function testGetter(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = new UserCreatePostEvent($user);

        $this->assertEquals($user, $sut->getUser());
    }
}
