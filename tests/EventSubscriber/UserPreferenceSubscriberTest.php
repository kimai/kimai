<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Event\PrepareUserEvent;
use App\EventSubscriber\UserPreferenceSubscriber;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[CoversClass(UserPreferenceSubscriber::class)]
class UserPreferenceSubscriberTest extends TestCase
{
    public const EXPECTED_PREFERENCES = [
        'hourly_rate',
        'internal_rate',
        'timezone',
        'language',
        'locale',
        'first_weekday',
        'skin',
        'update_browser_title',
        'calendar_initial_view',
        'login_initial_view',
        'daily_stats',
        'export_decimal',
        'favorite_routes',
    ];

    public function testGetSubscribedEvents(): void
    {
        $events = UserPreferenceSubscriber::getSubscribedEvents();
        self::assertArrayHasKey(PrepareUserEvent::class, $events);
        $methodName = $events[PrepareUserEvent::class][0];
        self::assertIsString($methodName);
        self::assertTrue(method_exists(UserPreferenceSubscriber::class, $methodName));
    }

    public function testWithHourlyRateAllowed(): void
    {
        $sut = $this->getSubscriber(true);
        $user = new User();
        $event = new PrepareUserEvent($user);

        self::assertSame($user, $event->getUser());

        $prefs = $sut->getDefaultPreferences($user);
        foreach ($prefs as $pref) {
            self::assertTrue(\in_array($pref->getName(), self::EXPECTED_PREFERENCES), 'Unknown user preference: ' . $pref->getName());
        }

        self::assertCount(\count(self::EXPECTED_PREFERENCES), $prefs);

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

    public function testWithHourlyRateNotAllowed(): void
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
                    self::assertFalse($pref->isEnabled());
                    break;

                default:
                    self::assertTrue($pref->isEnabled());
            }
        }
    }

    public function testMissingLocaleDefaultsToUserLanguage(): void
    {
        $sut = $this->getSubscriber(false, 'en_AU');

        // an existing user (created before the locale was split from the language) has no stored locale:
        // formatting must keep using the language, the system default locale is only for new users
        $user = new User();
        $user->setLanguage('de');
        $sut->loadUserPreferences(new PrepareUserEvent($user));

        self::assertEquals('de', $user->getLocale());
        self::assertEquals('de', $user->getPreferenceValue(UserPreference::LOCALE));
    }

    public function testStoredLocaleIsKept(): void
    {
        $sut = $this->getSubscriber(false, 'en_AU');

        $user = new User();
        $user->setLanguage('de');
        $user->setLocale('de_CH');
        $sut->loadUserPreferences(new PrepareUserEvent($user));

        self::assertEquals('de_CH', $user->getLocale());
    }

    protected function getSubscriber(bool $seeHourlyRate, ?string $defaultLocale = null): UserPreferenceSubscriber
    {
        $authMock = $this->createMock(AuthorizationCheckerInterface::class);
        $authMock->method('isGranted')->willReturn($seeHourlyRate);

        $eventMock = $this->createMock(EventDispatcherInterface::class);
        $formConfigMock = SystemConfigurationFactory::createStub([
            'defaults' => [
                'user' => [
                    'language' => 'en',
                    'locale' => $defaultLocale,
                    'currency' => 'EUR',
                ]
            ]
        ]);

        return new UserPreferenceSubscriber($eventMock, $authMock, $formConfigMock);
    }
}
