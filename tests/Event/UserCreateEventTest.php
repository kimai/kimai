<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Event\AbstractUserEvent;
use App\Event\UserCreateEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractUserEvent::class)]
#[CoversClass(UserCreateEvent::class)]
class UserCreateEventTest extends TestCase
{
    public function testGetter(): void
    {
        $user = new User();
        $user->setAlias('foo');

        $sut = new UserCreateEvent($user);

        self::assertEquals($user, $sut->getUser());
    }
}
