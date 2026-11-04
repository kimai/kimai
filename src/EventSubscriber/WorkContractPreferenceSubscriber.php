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
use App\WorkingTime\Mode\WorkingTimeMode;
use App\WorkingTime\Mode\WorkingTimeModeFactory;
use App\WorkingTime\Mode\WorkingTimeModeNone;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

final class WorkContractPreferenceSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly AuthorizationCheckerInterface $voter, private readonly WorkingTimeModeFactory $modeFactory)
    {
    }

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
        $canEditContract = $this->voter->isGranted('contract', $user);

        $modes = array_map(fn (WorkingTimeMode $mode): string => $mode->getId(), $this->modeFactory->getAll());
        $workHours = [new Range(['min' => 0, 'max' => 86400])];
        $date = [new Date()];

        $prefs = [
            UserPreference::WORK_CONTRACT_TYPE => [WorkingTimeModeNone::ID, [new NotBlank(), new Choice(choices: $modes)]],
            WorkingTimeCalculatorDay::WORK_HOURS_MONDAY => [0, $workHours],
            WorkingTimeCalculatorDay::WORK_HOURS_TUESDAY => [0, $workHours],
            WorkingTimeCalculatorDay::WORK_HOURS_WEDNESDAY => [0, $workHours],
            WorkingTimeCalculatorDay::WORK_HOURS_THURSDAY => [0, $workHours],
            WorkingTimeCalculatorDay::WORK_HOURS_FRIDAY => [0, $workHours],
            WorkingTimeCalculatorDay::WORK_HOURS_SATURDAY => [0, $workHours],
            WorkingTimeCalculatorDay::WORK_HOURS_SUNDAY => [0, $workHours],
            UserPreference::PUBLIC_HOLIDAY_GROUP => [null, [new Length(['max' => 250])]],
            UserPreference::HOLIDAYS_PER_YEAR => [0.0, [new Range(['min' => 0])]],
            'work_start_day' => [null, $date],
            'work_last_day' => [null, $date],
        ];

        foreach ($prefs as $prefName => [$defaultValue, $constraints]) {
            $pref = $user->getPreference($prefName);
            if ($pref === null) {
                $pref = new UserPreference($prefName, $defaultValue);
                $user->addPreference($pref);
            }
            $pref->setConstraints($constraints);
            $pref->setEnabled($canEditContract);
        }
    }
}
