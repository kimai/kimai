<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Entity\UserPreference;
use App\Event\UserPreferenceDisplayEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserPreferenceDisplayEvent::class)]
class UserPreferenceDisplayEventTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $sut = new UserPreferenceDisplayEvent('blub');
        self::assertEquals('blub', $sut->getLocation());
        self::assertIsArray($sut->getPreferences());
        self::assertEmpty($sut->getPreferences());

        $preference = new UserPreference('foo', 'bar');
        $sut->addPreference($preference);

        self::assertEquals([$preference], $sut->getPreferences());
    }
}
