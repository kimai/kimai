<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\UserPreference;
use App\Event\UserPreferenceEvent;
use App\Form\Type\DurationType;
use App\WorkingTime\Calculator\WorkingTimeCalculatorDay;
use App\WorkingTime\Mode\WorkingTimeModeFactory;
use App\WorkingTime\Mode\WorkingTimeModeNone;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

final class WorkContractPreferenceSubscriber implements EventSubscriberInterface
{
    public function __construct(private WorkingTimeModeFactory $modeFactory, private AuthorizationCheckerInterface $voter)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserPreferenceEvent::class => ['onUserPreference', 100]
        ];
    }

    public function onUserPreference(UserPreferenceEvent $event): void
    {
        $user = $event->getUser();
        $canEditContract = $this->voter->isGranted('contract', $user);

        $durationConstraints = [
            new GreaterThanOrEqual(0),
            new LessThanOrEqual(86400),
        ];

        $modes = [];
        foreach ($this->modeFactory->getAll() as $mode) {
            $modes[$mode->getName()] = $mode->getId();
        }

        $event->addPreference(
            (new UserPreference(UserPreference::WORK_CONTRACT_TYPE, WorkingTimeModeNone::ID))
                ->setOrder(2000)
                ->setSection('work_contract')
                ->setType(ChoiceType::class)
                ->setEnabled($canEditContract)
                ->setOptions(['choices' => $modes, 'label' => 'work_hours_mode'])
        );

        $event->addPreference(
            (new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_MONDAY, 0))
                ->setOrder(2010)
                ->setSection('work_contract')
                ->setType(DurationType::class)
                ->setEnabled($canEditContract)
                ->setConstraints($durationConstraints)
                ->setOptions(['label' => 'Monday'])
        );

        $event->addPreference(
            (new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_TUESDAY, 0))
                ->setOrder(2020)
                ->setSection('work_contract')
                ->setType(DurationType::class)
                ->setEnabled($canEditContract)
                ->setConstraints($durationConstraints)
                ->setOptions(['label' => 'Tuesday'])
        );

        $event->addPreference(
            (new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_WEDNESDAY, 0))
                ->setOrder(2030)
                ->setSection('work_contract')
                ->setType(DurationType::class)
                ->setEnabled($canEditContract)
                ->setConstraints($durationConstraints)
                ->setOptions(['label' => 'Wednesday'])
        );

        $event->addPreference(
            (new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_THURSDAY, 0))
                ->setOrder(2040)
                ->setSection('work_contract')
                ->setType(DurationType::class)
                ->setEnabled($canEditContract)
                ->setConstraints($durationConstraints)
                ->setOptions(['label' => 'Thursday'])
        );

        $event->addPreference(
            (new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_FRIDAY, 0))
                ->setOrder(2050)
                ->setSection('work_contract')
                ->setType(DurationType::class)
                ->setEnabled($canEditContract)
                ->setConstraints($durationConstraints)
                ->setOptions(['label' => 'Friday'])
        );

        $event->addPreference(
            (new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_SATURDAY, 0))
                ->setOrder(2060)
                ->setSection('work_contract')
                ->setType(DurationType::class)
                ->setEnabled($canEditContract)
                ->setConstraints($durationConstraints)
                ->setOptions(['label' => 'Saturday'])
        );

        $event->addPreference(
            (new UserPreference(WorkingTimeCalculatorDay::WORK_HOURS_SUNDAY, 0))
                ->setOrder(2070)
                ->setSection('work_contract')
                ->setType(DurationType::class)
                ->setEnabled($canEditContract)
                ->setConstraints($durationConstraints)
                ->setOptions(['label' => 'Sunday'])
        );

        $event->addPreference(
            (new UserPreference(UserPreference::PUBLIC_HOLIDAY_GROUP, null))
                ->setOrder(2080)
                ->setSection('work_contract')
                ->setType(ChoiceType::class)
                ->setEnabled($canEditContract)
                ->setOptions(['required' => false, 'label' => 'public_holiday_group'])
        );

        $event->addPreference(
            (new UserPreference(UserPreference::HOLIDAYS_PER_YEAR, 0.0))
                ->setOrder(2090)
                ->setSection('work_contract')
                ->setType(NumberType::class)
                ->setEnabled($canEditContract)
                ->setConstraints([new GreaterThanOrEqual(0)])
                ->setOptions(['label' => 'holidays', 'required' => false])
        );

        $event->addPreference(
            (new UserPreference('work_start_day', null))
                ->setOrder(2100)
                ->setSection('work_contract')
                ->setType(DateType::class)
                ->setEnabled($canEditContract)
                ->setOptions(['required' => false, 'label' => 'work_start_day'])
        );

        $event->addPreference(
            (new UserPreference('work_last_day', null))
                ->setOrder(2110)
                ->setSection('work_contract')
                ->setType(DateType::class)
                ->setEnabled($canEditContract)
                ->setOptions(['required' => false, 'label' => 'work_last_day'])
        );
    }
}
