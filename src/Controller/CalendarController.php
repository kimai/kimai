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
use App\Entity\User;
use App\Form\CalendarForm;
use App\Timesheet\TrackingModeService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    private $configuration;
    private $service;

    public function __construct(CalendarService $calendarService, SystemConfiguration $configuration, TrackingModeService $service)
    {
        $this->calendarService = $calendarService;
        $this->configuration = $configuration;
        $this->service = $service;
    }

    /**
     * @Route(path="/", name="calendar", methods={"GET"})
     * @Route(path="/{profile}", name="calendar_user", methods={"GET"})
     */
    public function userCalendar(Request $request): Response
    {
        $form = null;
        $profile = $this->getUser();

        if ($this->isGranted('view_other_timesheet')) {
            $form = $this->createFormForGetRequest(CalendarForm::class, ['user' => $profile], [
                'action' => $this->generateUrl('calendar'),
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $values = $form->getData();
                if ($values['user'] instanceof User) {
                    $profile = $values['user'];
                }
            }

            $form = $form->createView();

            // hide if the current user is the only available one
            if (\count($form->offsetGet('user')->vars['choices']) < 2) {
                $form = null;
                $profile = $this->getUser();
            }
        }

        $mode = $this->service->getActiveMode();
        $factory = $this->getDateTimeFactory();
        $defaultStart = $factory->createDateTime($this->configuration->getTimesheetDefaultBeginTime());

        $config = $this->calendarService->getConfiguration();

        $isPunchMode = !$mode->canEditDuration() && !$mode->canEditBegin() && !$mode->canEditEnd();
        $dragAndDrop = [];

        if ($mode->canEditBegin()) {
            try {
                $dragAndDrop = $this->calendarService->getDragAndDropResources($profile);
            } catch (\Exception $ex) {
                $this->logException($ex);
            }
        }

        return $this->render('calendar/user.html.twig', [
            'form' => $form,
            'user' => $profile,
            'config' => $config,
            'dragAndDrop' => $dragAndDrop,
            'google' => $this->calendarService->getGoogleSources($profile),
            'now' => $factory->createDateTime(),
            'defaultStartTime' => $defaultStart->format('h:i:s'),
            'is_punch_mode' => $isPunchMode,
            'can_edit_begin' => $mode->canEditBegin(),
            'can_edit_end' => $mode->canEditBegin(),
            'can_edit_duration' => $mode->canEditDuration(),
        ]);
    }
}
