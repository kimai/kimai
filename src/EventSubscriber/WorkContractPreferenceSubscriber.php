<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\UserPreference;
use App\Event\RegisterUserPreferencesEvent;
use App\WorkingTime\Calculator\WorkingTimeCalculatorDay;
use App\WorkingTime\Mode\WorkingTimeModeNone;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class WorkContractPreferenceSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RegisterUserPreferencesEvent::class => ['onRegisterUserPreferences', 100]
        ];
    }

    public function onRegisterUserPreferences(RegisterUserPreferencesEvent $event): void
    {
        $user = $event->getUser();

        if (null === $user->getPreference(UserPreference::WORK_CONTRACT_TYPE)) {
            $user->addPreference(new UserPreference(UserPreference::WORK_CONTRACT_TYPE, WorkingTimeModeNone::ID));
        }

        if (null === $user->getPreference(WorkingTimeCalculatorDay::WORK_HOURS_MONDAY)) {
            $user->addPreference(new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_MONDAY, 0));
        }

        if (null === $user->getPreference(WorkingTimeCalculatorDay::WORK_HOURS_TUESDAY)) {
            $user->addPreference(new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_TUESDAY, 0));
        }

        if (null === $user->getPreference(WorkingTimeCalculatorDay::WORK_HOURS_WEDNESDAY)) {
            $user->addPreference(new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_WEDNESDAY, 0));
        }

        if (null === $user->getPreference(WorkingTimeCalculatorDay::WORK_HOURS_THURSDAY)) {
            $user->addPreference(new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_THURSDAY, 0));
        }

        if (null === $user->getPreference(WorkingTimeCalculatorDay::WORK_HOURS_FRIDAY)) {
            $user->addPreference(new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_FRIDAY, 0));
        }

        if (null === $user->getPreference(WorkingTimeCalculatorDay::WORK_HOURS_SATURDAY)) {
            $user->addPreference(new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_SATURDAY, 0));
        }

        if (null === $user->getPreference(WorkingTimeCalculatorDay::WORK_HOURS_SUNDAY)) {
            $user->addPreference(new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_SUNDAY, 0));
        }

        if (null === $user->getPreference(UserPreference::PUBLIC_HOLIDAY_GROUP)) {
            $user->addPreference(new UserPreference(UserPreference::PUBLIC_HOLIDAY_GROUP, null));
        }

        if (null === $user->getPreference(UserPreference::HOLIDAYS_PER_YEAR)) {
            $user->addPreference(new UserPreference(UserPreference::HOLIDAYS_PER_YEAR, 0.0));
        }

        if (null === $user->getPreference('work_start_day')) {
            $user->addPreference(new UserPreference('work_start_day', null));
        }

        if (null === $user->getPreference('work_last_day')) {
            $user->addPreference(new UserPreference('work_last_day', null));
        }
    }
}
