<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Event\UserCreateEvent;
use App\Event\UserPreferenceEvent;
use App\WorkingTime\Calculator\WorkingTimeCalculatorDay;
use App\WorkingTime\Mode\WorkingTimeModeNone;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class WorkContractPreferenceSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserPreferenceEvent::class => ['registerDefaultUserPreferences', 100],
            UserCreateEvent::class => ['registerNewUserPreferences', 100]
        ];
    }

    public function registerDefaultUserPreferences(UserPreferenceEvent $event): void
    {
        if ($event->isBooting()) {
            return;
        }

        $this->registerUserPreferences($event->getUser());
    }

    public function registerNewUserPreferences(UserCreateEvent $event): void
    {
        $this->registerUserPreferences($event->getUser());
    }

    private function registerUserPreferences(User $user): void
    {
        $prefs = [
            UserPreference::WORK_CONTRACT_TYPE => WorkingTimeModeNone::ID,
            WorkingTimeCalculatorDay::WORK_HOURS_MONDAY => 0,
            WorkingTimeCalculatorDay::WORK_HOURS_TUESDAY => 0,
            WorkingTimeCalculatorDay::WORK_HOURS_WEDNESDAY => 0,
            WorkingTimeCalculatorDay::WORK_HOURS_THURSDAY => 0,
            WorkingTimeCalculatorDay::WORK_HOURS_FRIDAY => 0,
            WorkingTimeCalculatorDay::WORK_HOURS_SATURDAY => 0,
            WorkingTimeCalculatorDay::WORK_HOURS_SUNDAY => 0,
            UserPreference::PUBLIC_HOLIDAY_GROUP => null,
            UserPreference::HOLIDAYS_PER_YEAR => 0.0,
            'work_start_day' => null,
            'work_last_day' => null,
        ];

        foreach ($prefs as $prefName => $defaultValue) {
            if ($user->getPreference($prefName) === null) {
                $user->addPreference(new UserPreference($prefName, $defaultValue));
            }
        }
    }
}
