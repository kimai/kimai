<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Configuration\FormConfiguration;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Event\PrepareUserEvent;
use App\EventSubscriber\UserPreferenceSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @covers \App\EventSubscriber\UserPreferenceSubscriber
 */
class UserPreferenceSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $events = UserPreferenceSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(PrepareUserEvent::class, $events);
        $methodName = $events[PrepareUserEvent::class][0];
        $this->assertTrue(method_exists(UserPreferenceSubscriber::class, $methodName));
    }

    public function testWithHourlyRateAllowed()
    {
        $sut = $this->getSubscriber(true);
        $user = new User();
        $event = new PrepareUserEvent($user);

        self::assertSame($user, $event->getUser());

        $prefs = $sut->getDefaultPreferences($user);
        self::assertCount(12, $prefs);

        foreach ($prefs as $pref) {
            switch ($pref->getName()) {
                case UserPreference::HOURLY_RATE:
                case UserPreference::INTERNAL_RATE:
                    self::assertTrue($pref->isEnabled());
                    break;

                case UserPreference::FIRST_WEEKDAY:
                    self::assertEquals('monday', $pref->getValue());
                    break;

                default:
                    self::assertTrue($pref->isEnabled());
            }
        }
    }

    public function testWithHourlyRateNotAllowed()
    {
        $sut = $this->getSubscriber(false);
        $user = new User();
        $event = new PrepareUserEvent($user);

        self::assertSame($user, $event->getUser());

        // TODO test merging values
        $sut->loadUserPreferences($event);
        $prefs = $event->getUser()->getPreferences();
        self::assertCount(12, $prefs);

        foreach ($prefs as $pref) {
            switch ($pref->getName()) {
                case UserPreference::HOURLY_RATE:
                case UserPreference::INTERNAL_RATE:
                    self::assertFalse($pref->isEnabled());
                break;

                default:
                    self::assertTrue($pref->isEnabled());
            }
        }
    }

    protected function getSubscriber(bool $seeHourlyRate)
    {
        $authMock = $this->createMock(AuthorizationCheckerInterface::class);
        $authMock->expects($this->once())->method('isGranted')->willReturn($seeHourlyRate);

        $eventMock = $this->createMock(EventDispatcherInterface::class);
        $formConfigMock = $this->createMock(FormConfiguration::class);

        return new UserPreferenceSubscriber($eventMock, $authMock, $formConfigMock);
    }
}
