<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Configuration\SystemConfiguration;
use App\Controller\AbstractController;
use App\Model\DailyStatistic;
use App\Model\MonthlyStatistic;
use App\Reporting\MonthlyUserList;
use App\Reporting\MonthlyUserListForm;
use App\Reporting\WeeklyUserList;
use App\Reporting\WeeklyUserListForm;
use App\Reporting\YearlyUserList;
use App\Reporting\YearlyUserListForm;
use App\Repository\Query\UserQuery;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use App\Timesheet\TimesheetStatisticService;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/reporting")
 * @Security("is_granted('view_reporting') and is_granted('view_other_reporting') and is_granted('view_other_timesheet')")
 */
final class ReportUsersListController extends AbstractController
{
    private $timesheetRepository;
    private $userRepository;

    public function __construct(TimesheetRepository $timesheetRepository, UserRepository $userRepository)
    {
        $this->timesheetRepository = $timesheetRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route(path="/yearly_users_list", name="report_yearly_users", methods={"GET","POST"})
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function yearlyUsersList(Request $request, SystemConfiguration $systemConfiguration, TimesheetStatisticService $statisticService): Response
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory();

        $query = new UserQuery();
        $query->setCurrentUser($currentUser);
        $allUsers = $this->userRepository->getUsersForQuery($query);
        $defaultDate = $dateTimeFactory->createDateTime('01 january this year 00:00:00');

        if (null !== ($financialYear = $systemConfiguration->getFinancialYearStart())) {
            $defaultDate = $this->getDateTimeFactory()->createStartOfFinancialYear($financialYear);
        }

        $values = new YearlyUserList();
        $values->setDate(clone $defaultDate);

        $form = $this->createForm(YearlyUserListForm::class, $values, [
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
        ]);

        $form->submit($request->query->all(), false);

        if ($form->isSubmitted() && !$form->isValid()) {
            $values->setDate(clone $defaultDate);
        }

        if ($values->getDate() === null) {
            $values->setDate(clone $defaultDate);
        }

        $start = $values->getDate();
        // there is a potential edge case bug for financial years:
        // the last month will be skipped, if the financial year started on a different day than the first
        $end = $dateTimeFactory->createEndOfFinancialYear($start);

        $monthStats = [];
        $hasData = true;

        if (!empty($allUsers)) {
            $monthStats = $statisticService->getMonthlyStats($start, $end, $allUsers);
        }

        if (empty($monthStats)) {
            $monthStats = [new MonthlyStatistic($start, $end, $currentUser)];
            $hasData = false;
        }

        return $this->render('reporting/report_user_list_monthly.html.twig', [
            'report_title' => 'report_yearly_users',
            'box_id' => 'yearly-user-list-reporting-box',
            'form' => $form->createView(),
            'stats' => $monthStats,
            'hasData' => $hasData,
        ]);
    }

    /**
     * @Route(path="/monthly_users_list", name="report_monthly_users", methods={"GET","POST"})
     */
    public function monthlyUsersList(Request $request, TimesheetStatisticService $statisticService): Response
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory();

        $query = new UserQuery();
        $query->setCurrentUser($currentUser);
        $allUsers = $this->userRepository->getUsersForQuery($query);

        $values = new MonthlyUserList();
        $values->setDate($dateTimeFactory->getStartOfMonth());

        $form = $this->createForm(MonthlyUserListForm::class, $values, [
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
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

        $previous = clone $start;
        $previous->modify('-1 month');

        $next = clone $start;
        $next->modify('+1 month');

        $dayStats = [];
        $hasData = true;

        if (!empty($allUsers)) {
            $dayStats = $statisticService->getDailyStatistics($start, $end, $allUsers);
        }

        if (empty($dayStats)) {
            $dayStats = [new DailyStatistic($start, $end, $currentUser)];
            $hasData = false;
        }

        return $this->render('reporting/report_user_list.html.twig', [
            'report_title' => 'report_monthly_users',
            'box_id' => 'monthly-user-list-reporting-box',
            'form' => $form->createView(),
            'current' => $start,
            'next' => $next,
            'previous' => $previous,
            'subReportDate' => $values->getDate(),
            'subReportRoute' => 'report_user_month',
            'stats' => $dayStats,
            'hasData' => $hasData,
        ]);
    }

    /**
     * @Route(path="/weekly_users_list", name="report_weekly_users", methods={"GET","POST"})
     */
    public function weeklyUsersList(Request $request, TimesheetStatisticService $statisticService): Response
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory();

        $query = new UserQuery();
        $query->setCurrentUser($currentUser);
        $allUsers = $this->userRepository->getUsersForQuery($query);

        $values = new WeeklyUserList();
        $values->setDate($dateTimeFactory->getStartOfWeek());

        $form = $this->createForm(WeeklyUserListForm::class, $values, [
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
        ]);

        $form->submit($request->query->all(), false);

        if ($form->isSubmitted() && !$form->isValid()) {
            $values->setDate($dateTimeFactory->getStartOfWeek());
        }

        if ($values->getDate() === null) {
            $values->setDate($dateTimeFactory->getStartOfWeek());
        }

        $start = $dateTimeFactory->getStartOfWeek($values->getDate());
        $end = $dateTimeFactory->getEndOfWeek($values->getDate());

        $previous = clone $start;
        $previous->modify('-1 week');

        $next = clone $start;
        $next->modify('+1 week');

        $dayStats = [];
        $hasData = true;

        if (!empty($allUsers)) {
            $dayStats = $statisticService->getDailyStatistics($start, $end, $allUsers);
        }

        if (empty($dayStats)) {
            $dayStats = [new DailyStatistic($start, $end, $currentUser)];
            $hasData = false;
        }

        return $this->render('reporting/report_user_list.html.twig', [
            'report_title' => 'report_weekly_users',
            'box_id' => 'weekly-user-list-reporting-box',
            'form' => $form->createView(),
            'current' => $start,
            'next' => $next,
            'previous' => $previous,
            'subReportDate' => $values->getDate(),
            'subReportRoute' => 'report_user_week',
            'stats' => $dayStats,
            'hasData' => $hasData,
        ]);
    }
}
