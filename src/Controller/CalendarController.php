<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Calendar\CalendarQuery;
use App\Calendar\CalendarService;
use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Form\Toolbar\CalendarToolbarForm;
use App\Form\Type\CalendarViewType;
use App\Timesheet\TrackingModeService;
use App\Utils\PageSetup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to display calendars.
 */
#[Route(path: '/calendar')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class CalendarController extends AbstractController
{
    public function __construct(private CalendarService $calendarService, private SystemConfiguration $configuration, private TrackingModeService $service)
    {
    }

    #[Route(path: '/', name: 'calendar', methods: ['GET'])]
    #[Route(path: '/{profile}', name: 'calendar_user', methods: ['GET'])]
    public function userCalendar(Request $request): Response
    {
        $currentUser = $this->getUser();
        $profile = $currentUser;
        $canChangeUser = $this->isGranted('view_other_timesheet');

        $query = new CalendarQuery();
        $query->setUser($profile);
        $query->setDate($this->getDateTimeFactory($profile)->create());

        $defaultView = CalendarViewType::DEFAULT_VIEW;
        $userView = $profile->getPreference('calendar_initial_view')?->getValue();
        if ($userView !== null) {
            $defaultView = (string) $userView;
        }
        $query->setView($defaultView);

        $form = $this->createFormForGetRequest(CalendarToolbarForm::class, $query, [
            'action' => $this->generateUrl('calendar'),
            'change_user' => $canChangeUser,
        ]);

        $form->submit($request->query->all(), false);

        if ($query->getUser() === null) {
            $query->setUser($currentUser);
        }

        /** @var User $profile */
        $profile = $query->getUser();

        if ($currentUser !== $profile && !$canChangeUser) {
            throw new AccessDeniedException('User is not allowed to see other users calendar');
        }

        $mode = $this->service->getActiveMode();
        $factory = $this->getDateTimeFactory($profile);

        // if now is default time, we do not pass it on, so it can be re-calculated for each new entry
        $defaultStart = null;
        if ($this->configuration->getTimesheetDefaultBeginTime() !== 'now') {
            $defaultStart = $factory->createDateTime($this->configuration->getTimesheetDefaultBeginTime());
            $defaultStart = $defaultStart->format('H:i:s');
        }

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

        $page = new PageSetup('calendar');
        $page->setHelp('calendar.html');

        return $this->render('calendar/user.html.twig', [
            'page_setup' => $page,
            'initial_view' => $query->getView(),
            'initial_date' => $query->getDate(),
            'form' => $form->createView(),
            'user' => $profile,
            'config' => $config,
            'dragAndDrop' => $dragAndDrop,
            'google' => $this->calendarService->getGoogleSources($profile),
            'sources' => $this->calendarService->getSources($profile),
            'now' => $factory->createDateTime(),
            'defaultStartTime' => $defaultStart,
            'is_punch_mode' => $isPunchMode,
            'can_edit_begin' => $mode->canEditBegin(),
            'can_edit_end' => $mode->canEditBegin(),
            'can_edit_duration' => $mode->canEditDuration(),
        ]);
    }
}
