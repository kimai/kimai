<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Event\UserPreferenceEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\UserPreferenceEvent
 */
class UserPreferenceEventTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $user = new User();
        $user->setAlias('foo');
        $pref = new UserPreference('foo', 'bar');

        $sut = new UserPreferenceEvent($user, []);

        self::assertEquals($user, $sut->getUser());
        self::assertTrue($sut->isBooting());
        self::assertEquals([], $sut->getPreferences());

        $sut->addPreference($pref);

        self::assertEquals([$pref], $sut->getPreferences());

        $sut = new UserPreferenceEvent($user, [], false);
        self::assertFalse($sut->isBooting());
    }

    public function testDuplicatePreferenceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new User();
        $user->setAlias('foo');
        $pref = new UserPreference('foo', 'bar');
        $pref2 = new UserPreference('foo', 'hello');

        $sut = new UserPreferenceEvent($user, []);

        $sut->addPreference($pref);
        $sut->addPreference($pref2);
    }
}
