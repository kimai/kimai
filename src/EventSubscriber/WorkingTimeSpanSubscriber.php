<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Configuration\SystemConfiguration;
use App\Event\WorkingTimeYearEvent;
use App\WorkingTime\TimeSpanCalculator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class WorkingTimeSpanSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SystemConfiguration $systemConfiguration,
        private readonly TimeSpanCalculator $timeSpanCalculator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priority > 150: must run before WorkContractBundle subscribers that read actualTime
            WorkingTimeYearEvent::class => ['onWorkingTimeYear', 200],
        ];
    }

    public function onWorkingTimeYear(WorkingTimeYearEvent $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $year = $event->getYear();
        $user = $year->getUser();
        $until = $event->getUntil();

        $yearDate = $year->getYear();
        $stats = $this->timeSpanCalculator->calculateForYear($user, $yearDate);

        foreach ($year->getMonths() as $month) {
            foreach ($month->getDays() as $day) {
                $dayDate = $day->getDay();

                // Only process days up to "until" and not locked days
                if ($dayDate > $until || $day->isLocked()) {
                    continue;
                }

                $workingTime = $day->getWorkingTime();
                if ($workingTime === null) {
                    continue;
                }

                $key = $dayDate->format('Y-m-d');
                if (\array_key_exists($key, $stats)) {
                    $workingTime->setActualTime($stats[$key]);
                }
            }
        }
    }

    private function isEnabled(): bool
    {
        return (bool) $this->systemConfiguration->find('working_time_calc.enabled');
    }
}
