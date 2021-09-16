<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\SystemConfiguration;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to enter times in weekly form.
 *
 * @Route(path="/quick-entry")
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
class QuickEntryController extends AbstractController
{
    /**
     * @Route(path="/", name="quick-entry", methods={"GET"})
     */
    public function quickEntry(SystemConfiguration $configuration, TimesheetService $timesheetService, TimesheetRepository $repository)
    {
        $mode = $timesheetService->getActiveTrackingMode();
        $factory = $this->getDateTimeFactory();
        $defaultStart = $factory->createDateTime($configuration->getTimesheetDefaultBeginTime());

        if (!$mode->canEditDuration() && !$mode->canEditEnd()) {
            $this->flashError('Not allowed');

            return $this->redirectToRoute('homepage');
        }

        // TODO form handling

        $startWeek = $factory->getStartOfWeek();
        $endWeek = $factory->getEndOfWeek();
        $tmpDay = clone $startWeek;
        $week = [];
        while ($tmpDay < $endWeek) {
            $nextDay = clone $tmpDay;
            $week[$nextDay->format('Y-m-d')] = $nextDay;
            $tmpDay = $tmpDay->modify('+1 day');
        }

        dd($week);

        $query = new TimesheetQuery();
        $query->setName('quickEntryForm');
        $query->setUser($this->getUser());

        $result = $repository->getTimesheetResult($query);
        $timesheets = $result->getResults();
        foreach ($timesheets as $timesheet) {
        }

        return $this->render('quick-entry/index.html.twig', [
            'now' => $factory->createDateTime(),
            'default_start' => $defaultStart,
            'default_start_time' => $defaultStart->format('h:i:s'),
            'can_edit_begin' => $mode->canEditBegin(),
            'can_edit_end' => $mode->canEditBegin(),
            'can_edit_duration' => $mode->canEditDuration(),
        ]);
    }
}
