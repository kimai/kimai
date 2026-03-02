<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\SystemConfigurationEvent;
use App\Form\Model\Configuration;
use App\Form\Model\SystemConfiguration;
use App\Form\Type\YesNoType;
use App\WorkingTime\TimeSpanCalculator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\Range;

final class WorkingTimeSpanConfigurationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigurationEvent::class => ['onSystemConfiguration', 100],
        ];
    }

    public function onSystemConfiguration(SystemConfigurationEvent $event): void
    {
        $event->addConfiguration(
            (new SystemConfiguration('working_time_calc'))
                ->setTranslationDomain('messages')
                ->setConfiguration([
                    (new Configuration('working_time_calc.enabled'))
                        ->setLabel('working_time_calc.enabled')
                        ->setType(YesNoType::class)
                        ->setValue(true)
                        ->setOptions([
                            'help' => 'working_time_calc.enabled.help',
                        ]),
                    (new Configuration('working_time_calc.gap_tolerance'))
                        ->setLabel('working_time_calc.gap_tolerance')
                        ->setType(IntegerType::class)
                        ->setValue(TimeSpanCalculator::DEFAULT_GAP_TOLERANCE_MINUTES)
                        ->setConstraints([new Range(['min' => 0, 'max' => 60])])
                        ->setOptions([
                            'help' => 'working_time_calc.gap_tolerance.help',
                        ]),
                    (new Configuration('working_time_calc.max_duration'))
                        ->setLabel('working_time_calc.max_duration')
                        ->setType(IntegerType::class)
                        ->setValue(TimeSpanCalculator::DEFAULT_MAX_DURATION_HOURS)
                        ->setConstraints([new Range(['min' => 1, 'max' => 24])])
                        ->setOptions([
                            'help' => 'working_time_calc.max_duration.help',
                        ]),
                ])
        );
    }
}
