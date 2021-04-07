<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Model\Statistic\Day;
use App\Reporting\MonthlyUserList;
use App\Reporting\MonthlyUserListForm;
use App\Reporting\ReportingService;
use App\Repository\Query\UserQuery;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to render reports.
 *
 * @Route(path="/reporting")
 * @Security("is_granted('view_reporting')")
 */
final class ReportingController extends AbstractController
{
    /**
     * @var TimesheetRepository
     */
    private $timesheetRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(TimesheetRepository $timesheetRepository, UserRepository $userRepository)
    {
        $this->timesheetRepository = $timesheetRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route(path="/", name="reporting", methods={"GET"})
     *
     * @return Response
     */
    public function defaultReport(ReportingService $reportingService): Response
    {
        $user = $this->getUser();
        $route = null;

        $defaultReport = $user->getPreferenceValue('reporting.initial_view', ReportingService::DEFAULT_VIEW);
        $allReports = $reportingService->getAvailableReports($user);

        foreach ($allReports as $report) {
            if ($report->getId() === $defaultReport) {
                $route = $report->getRoute();
                break;
            }
        }

        // fallback, if the configured report could not be found
        // eg. when it was deleted or replaced by an enhanced version with a new id
        if ($route === null && \count($allReports) > 0) {
            $report = $allReports[array_keys($allReports)[0]];
            $route = $report->getRoute();
        }

        if ($route === null) {
            throw $this->createNotFoundException('Unknown default report');
        }

        return $this->redirectToRoute($route);
    }

    /**
     * @Route(path="/monthly_users_list", name="report_monthly_users", methods={"GET","POST"})
     * @Security("is_granted('view_other_timesheet')")
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function monthlyUsersList(Request $request): Response
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory();
        $localeFormats = $this->getLocaleFormats($request->getLocale());

        $query = new UserQuery();
        $query->setCurrentUser($currentUser);
        $allUsers = $this->userRepository->getUsersForQuery($query);

        $rows = [];

        $values = new MonthlyUserList();
        $values->setDate($dateTimeFactory->getStartOfMonth());

        $form = $this->createForm(MonthlyUserListForm::class, $values, [
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
            'format' => $localeFormats->getDateTypeFormat(),
        ]);

        $form->submit($request->query->all(), false);

        if ($form->isSubmitted() && !$form->isValid()) {
            $values->setDate($dateTimeFactory->getStartOfMonth());
        }

        if ($values->getDate() === null) {
            $values->setDate($dateTimeFactory->getStartOfMonth());
        }

        $start = $values->getDate();
        $start->modify('first day of 00:00:00');

        $end = clone $start;
        $end->modify('last day of 23:59:59');

        $previousMonth = clone $start;
        $previousMonth->modify('-1 month');

        $nextMonth = clone $start;
        $nextMonth->modify('+1 month');

        foreach ($allUsers as $user) {
            $rows[] = [
                'days' => $this->timesheetRepository->getDailyStats($user, $start, $end),
                'user' => $user
            ];
        }

        $days = [];

        if (isset($rows[0])) {
            /** @var Day $day */
            foreach ($rows[0]['days'] as $day) {
                $days[$day->getDay()->format('Ymd')] = $day->getDay();
            }
        }

        return $this->render('reporting/monthly_user_list.html.twig', [
            'form' => $form->createView(),
            'rows' => $rows,
            'days' => $days,
            'current' => $start,
            'next' => $nextMonth,
            'previous' => $previousMonth,
        ]);
    }
}
