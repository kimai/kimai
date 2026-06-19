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
use App\Event\UserCreateEvent;
use App\EventSubscriber\WorkContractPreferenceSubscriber;
use App\WorkingTime\Calculator\WorkingTimeCalculatorDay;
use App\WorkingTime\Mode\WorkingTimeModeNone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkContractPreferenceSubscriber::class)]
class WorkContractPreferenceSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = WorkContractPreferenceSubscriber::getSubscribedEvents();

        self::assertArrayHasKey(UserCreateEvent::class, $events);
        self::assertSame(['onRegisterUserPreferences', 100], $events[UserCreateEvent::class]);
    }

    public function testOnRegisterUserPreferencesAddsMissingDefaults(): void
    {
        $user = new User();

        $sut = new WorkContractPreferenceSubscriber();
        $sut->onRegisterUserPreferences(new UserCreateEvent($user));

        self::assertCount(12, $user->getPreferences());
        self::assertSame(WorkingTimeModeNone::ID, $user->getWorkContractMode());
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_MONDAY));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_TUESDAY));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_WEDNESDAY));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_THURSDAY));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_FRIDAY));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_SATURDAY));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_SUNDAY));
        self::assertNull($user->getPreferenceValue(UserPreference::PUBLIC_HOLIDAY_GROUP));
        self::assertSame('0', $user->getPreferenceValue(UserPreference::HOLIDAYS_PER_YEAR));
        self::assertNull($user->getPreferenceValue('work_start_day'));
        self::assertNull($user->getPreferenceValue('work_last_day'));
        self::assertSame($user, $user->getPreference(UserPreference::WORK_CONTRACT_TYPE)?->getUser());
    }

    public function testOnRegisterUserPreferencesDoesNotOverwriteExistingValues(): void
    {
        $user = new User();
        $user->setWorkContractMode('custom');
        $user->setPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_MONDAY, 28800);
        $user->setPreferenceValue('work_start_day', '2026-01-01');

        $sut = new WorkContractPreferenceSubscriber();
        $sut->onRegisterUserPreferences(new UserCreateEvent($user));

        self::assertSame('custom', $user->getWorkContractMode());
        self::assertSame('28800', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_MONDAY));
        self::assertSame('2026-01-01', $user->getPreferenceValue('work_start_day'));
        self::assertSame('0', $user->getPreferenceValue(WorkingTimeCalculatorDay::WORK_HOURS_TUESDAY));
        self::assertNull($user->getPreferenceValue('work_last_day'));
        self::assertCount(12, $user->getPreferences());
    }
}
