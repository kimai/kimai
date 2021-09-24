<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Configuration\SystemConfiguration;
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
    public const EXPECTED_PREFERENCES = [
        'hourly_rate',
        'internal_rate',
        'timezone',
        'language',
        'first_weekday',
        'skin',
        'hours_24',
        'theme.layout',
        'theme.collapsed_sidebar',
        'theme.update_browser_title',
        'calendar.initial_view',
        'reporting.initial_view',
        'login.initial_view',
        'timesheet.daily_stats',
        'timesheet.export_decimal',
    ];

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
        foreach ($prefs as $pref) {
            $this->assertTrue(\in_array($pref->getName(), self::EXPECTED_PREFERENCES), 'Unknown user preference: ' . $pref->getName());
        }

        self::assertCount(\count(self::EXPECTED_PREFERENCES), $prefs);

        foreach ($prefs as $pref) {
            switch ($pref->getName()) {
                case UserPreference::HOURLY_RATE:
                case UserPreference::INTERNAL_RATE:
                case 'reporting.initial_view':
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
        self::assertCount(\count(self::EXPECTED_PREFERENCES), $prefs);

        foreach ($prefs as $pref) {
            switch ($pref->getName()) {
                case UserPreference::HOURLY_RATE:
                case UserPreference::INTERNAL_RATE:
                case 'reporting.initial_view':
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
        $authMock->method('isGranted')->willReturn($seeHourlyRate);

        $eventMock = $this->createMock(EventDispatcherInterface::class);
        $formConfigMock = $this->createMock(SystemConfiguration::class);

        return new UserPreferenceSubscriber($eventMock, $authMock, $formConfigMock);
    }
}
