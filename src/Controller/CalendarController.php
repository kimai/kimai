<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Calendar\CalendarService;
use App\Configuration\SystemConfiguration;
use App\Timesheet\TrackingModeService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to display calendars.
 *
 * @Route(path="/calendar")
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
class CalendarController extends AbstractController
{
    private $calendarService;

    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * @Route(path="/", name="calendar", methods={"GET"})
     */
    public function userCalendar(SystemConfiguration $configuration, TrackingModeService $service)
    {
        $mode = $service->getActiveMode();
        $factory = $this->getDateTimeFactory();
        $defaultStart = $factory->createDateTime($configuration->getTimesheetDefaultBeginTime());

        $config = [
            'dayLimit' => $configuration->getCalendarDayLimit(),
            'showWeekNumbers' => $configuration->isCalendarShowWeekNumbers(),
            'showWeekends' => $configuration->isCalendarShowWeekends(),
            'businessDays' => $configuration->getCalendarBusinessDays(),
            'businessTimeBegin' => $configuration->getCalendarBusinessTimeBegin(),
            'businessTimeEnd' => $configuration->getCalendarBusinessTimeEnd(),
            'slotDuration' => $configuration->getCalendarSlotDuration(),
            'timeframeBegin' => $configuration->getCalendarTimeframeBegin(),
            'timeframeEnd' => $configuration->getCalendarTimeframeEnd(),
            'dragDropAmount' => $configuration->getCalendarDragAndDropMaxEntries(),
        ];

        $isPunchMode = !$mode->canEditDuration() && !$mode->canEditBegin() && !$mode->canEditEnd();
        $dragAndDrop = [];

        if ($mode->canEditBegin()) {
            try {
                $dragAndDrop = $this->calendarService->getDragAndDropResources($this->getUser());
            } catch (\Exception $ex) {
                $this->logException($ex);
            }
        }

        return $this->render('calendar/user.html.twig', [
            'config' => $config,
            'dragAndDrop' => $dragAndDrop,
            'google' => $this->calendarService->getGoogleSources($this->getUser()),
            'now' => $factory->createDateTime(),
            'defaultStartTime' => $defaultStart->format('h:i:s'),
            'is_punch_mode' => $isPunchMode,
            'can_edit_begin' => $mode->canEditBegin(),
            'can_edit_end' => $mode->canEditBegin(),
            'can_edit_duration' => $mode->canEditDuration(),
        ]);
    }
}
