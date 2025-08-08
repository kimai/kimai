<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Event\CalendarConfigurationEvent;
use App\Event\CalendarDragAndDropSourceEvent;
use App\Event\CalendarGoogleSourceEvent;
use App\Event\CalendarSourceEvent;
use App\Event\RecentActivityEvent;
use App\Repository\TimesheetRepository;
use App\Utils\Color;
use Psr\EventDispatcher\EventDispatcherInterface;

final class CalendarService
{
    public function __construct(private SystemConfiguration $configuration, private TimesheetRepository $repository, private EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * @param User $user
     * @return DragAndDropSource[]
     * @throws \Exception
     */
    public function getDragAndDropResources(User $user): array
    {
        $maxAmount = $this->configuration->getCalendarDragAndDropMaxEntries();
        $event = new CalendarDragAndDropSourceEvent($user, $maxAmount);

        if ($maxAmount < 1) {
            return [];
        }

        $data = $this->repository->getRecentActivities($user, null, $maxAmount);

        $recentActivity = new RecentActivityEvent($user, $data);
        $this->dispatcher->dispatch($recentActivity);

        $entries = [];
        $colorHelper = new Color();
        $copy = $this->configuration->isCalendarDragAndDropCopyData();
        foreach ($recentActivity->getRecentActivities() as $timesheet) {
            $entries[] = new TimesheetEntry($timesheet, $colorHelper->getTimesheetColor($timesheet), $copy);
        }

        $event->addSource(new RecentActivitiesSource($entries));

        $this->dispatcher->dispatch($event);

        return $event->getSources();
    }

    public function getGoogleSources(User $user): ?Google
    {
        $apiKey = $this->configuration->getCalendarGoogleApiKey();
        if ($apiKey === null) {
            return null;
        }

        $sources = [];

        foreach ($this->configuration->getCalendarGoogleSources() as $name => $config) {
            $sources[] = new GoogleSource($name, $config['id'], $config['color']);
        }

        $event = new CalendarGoogleSourceEvent($user);
        $this->dispatcher->dispatch($event);

        foreach ($event->getSources() as $source) {
            $sources[] = $source;
        }

        return new Google($apiKey, $sources);
    }

    /**
     * @return array<CalendarSource>
     */
    public function getSources(User $user): array
    {
        $sources = [];

        $event = new CalendarSourceEvent($user);
        $this->dispatcher->dispatch($event);

        foreach ($event->getSources() as $source) {
            $sources[] = $source;
        }

        return $sources;
    }

    public function getConfiguration(): array
    {
        $config = [
            'dayLimit' => $this->configuration->getCalendarDayLimit(),
            'showWeekNumbers' => $this->configuration->isCalendarShowWeekNumbers(),
            'showWeekends' => $this->configuration->isCalendarShowWeekends(),
            'businessTimeBegin' => $this->configuration->getCalendarBusinessTimeBegin(),
            'businessTimeEnd' => $this->configuration->getCalendarBusinessTimeEnd(),
            'slotDuration' => $this->configuration->getCalendarSlotDuration(),
            'timeframeBegin' => $this->configuration->getCalendarTimeframeBegin(),
            'timeframeEnd' => $this->configuration->getCalendarTimeframeEnd(),
            'dragDropAmount' => $this->configuration->getCalendarDragAndDropMaxEntries(),
            'entryTitlePattern' => $this->configuration->find('calendar.title_pattern'),
        ];

        $event = new CalendarConfigurationEvent($config);
        $this->dispatcher->dispatch($event);

        return $event->getConfiguration();
    }
}
