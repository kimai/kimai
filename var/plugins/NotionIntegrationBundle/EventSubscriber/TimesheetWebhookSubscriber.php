<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\NotionIntegrationBundle\EventSubscriber;

use App\Event\TimesheetCreatePostEvent;
use App\Event\TimesheetStopPostEvent;
use App\Event\TimesheetUpdatePostEvent;
use KimaiPlugin\NotionIntegrationBundle\Service\WebhookService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TimesheetWebhookSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly WebhookService $webhookService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Use low priority (-100) to run after all other subscribers
            // This ensures the timesheet is fully saved before webhook fires
            TimesheetCreatePostEvent::class => ['onTimesheetCreated', -100],
            TimesheetUpdatePostEvent::class => ['onTimesheetUpdated', -100],
            TimesheetStopPostEvent::class => ['onTimesheetStopped', -100],
        ];
    }

    public function onTimesheetCreated(TimesheetCreatePostEvent $event): void
    {
        $this->webhookService->sendTimesheetWebhook(
            $event->getTimesheet(),
            'timesheet.created'
        );
    }

    public function onTimesheetUpdated(TimesheetUpdatePostEvent $event): void
    {
        $this->webhookService->sendTimesheetWebhook(
            $event->getTimesheet(),
            'timesheet.updated'
        );
    }

    public function onTimesheetStopped(TimesheetStopPostEvent $event): void
    {
        $this->webhookService->sendTimesheetWebhook(
            $event->getTimesheet(),
            'timesheet.stopped'
        );
    }
}


