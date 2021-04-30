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
use App\Model\Statistic\Day;
use App\Model\Statistic\Year;
use App\Reporting\MonthlyUserList;
use App\Reporting\MonthlyUserListForm;
use App\Reporting\WeeklyUserList;
use App\Reporting\WeeklyUserListForm;
use App\Reporting\YearlyUserList;
use App\Reporting\YearlyUserListForm;
use App\Repository\Query\UserQuery;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
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
     * @Route(path="/yearly_users_list", name="report_yearly_users", methods={"GET","POST"})
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function yearlyUsersList(Request $request, SystemConfiguration $systemConfiguration): Response
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory();
        $localeFormats = $this->getLocaleFormats($request->getLocale());

        $query = new UserQuery();
        $query->setCurrentUser($currentUser);
        $allUsers = $this->userRepository->getUsersForQuery($query);
        $defaultDate = $dateTimeFactory->createDateTime('01 january this year 00:00:00');

        $financialYear = $systemConfiguration->getFinancialYearStart();
        if (!empty($financialYear)) {
            try {
                $financialYear = $this->getDateTimeFactory()->createDateTime($financialYear);
                $year = clone $financialYear;
                $year->setDate((int) $defaultDate->format('Y'), (int) $financialYear->format('m'), (int) $financialYear->format('d'));
                $now = $dateTimeFactory->createDateTime();
                $now->setTime(0, 0, 0);
                if ($year >= $now) {
                    $year->modify('-1 year');
                }
                $defaultDate = clone $year;
            } catch (Exception $exception) {
            }
        }

        $rows = [];

        $values = new YearlyUserList();
        $values->setDate(clone $defaultDate);

        $form = $this->createForm(YearlyUserListForm::class, $values, [
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
            'format' => $localeFormats->getDateTypeFormat(),
        ]);

        $form->submit($request->query->all(), false);

        if ($form->isSubmitted() && !$form->isValid()) {
            $values->setDate(clone $defaultDate);
        }

        if ($values->getDate() === null) {
            $values->setDate(clone $defaultDate);
        }

        $start = $values->getDate();
        $start->setTime(0, 0, 0);

        $end = clone $start;
        $end->modify('+1 year')->modify('-1 day');

        $months = [];
        $totals = [];

        foreach ($allUsers as $user) {
            $rows[] = [
                'years' => $this->timesheetRepository->getMonthlyStats($start, $end, $user),
                'user' => $user
            ];
        }

        if (isset($rows[0])) {
            /** @var Year $year */
            foreach ($rows[0]['years'] as $year) {
                foreach ($year->getMonths() as $month) {
                    $date = new \DateTime();
                    $date->setDate((int) $year->getYear(), $month->getMonthNumber(), 1);
                    $date->setTime(0, 0, 0);
                    $months[$date->format('Ym')] = $date;
                }
            }
            foreach ($rows as $row) {
                foreach ($row['years'] as $year) {
                    foreach ($year->getMonths() as $month) {
                        $date = new \DateTime();
                        $date->setDate((int) $year->getYear(), $month->getMonthNumber(), 1);
                        $totalsId = $date->format('Ym');
                        if (!isset($totals[$totalsId])) {
                            $totals[$totalsId] = 0;
                        }
                        $totals[$totalsId] += $month->getTotalDuration();
                    }
                }
            }
        }
        /*
            foreach ($allUsers as $user) {
                $rows[] = [
                    'days' => $this->timesheetRepository->getDailyStats($user, $start, $end),
                    'user' => $user
                ];
            }

            $userYears = [];

            if (isset($rows[0])) {
                foreach ($rows[0]['days'] as $day) {
                    $months[$day->getDay()->format('Ym')] = $day->getDay();
                }
                foreach ($rows as $row) {
                    $userYear = ['user' => $row['user']];
                    foreach ($row['days'] as $day) {
                        $yearId = $day->getDay()->format('Y');
                        $monthId = $day->getDay()->format('m');
                        $totalsId = $yearId.$monthId;

                        if (!array_key_exists('years', $userYear)) {
                            $userYear['years'] = [];
                        }
                        if (!array_key_exists($yearId, $userYear['years'])) {
                            $userYear['years'][$yearId] = ['year' => $yearId];
                        }
                        if (!array_key_exists('months', $userYear['years'][$yearId])) {
                            $userYear['years'][$yearId]['months'] = [];
                        }
                        if (!array_key_exists($monthId, $userYear['years'][$yearId]['months'])) {
                            $userYear['years'][$yearId]['months'][$monthId] = ['month' => $monthId, 'totalDuration' => 0];
                        }
                        if (!array_key_exists($totalsId, $totals)) {
                            $totals[$totalsId] = 0;
                        }

                        $totals[$totalsId] += $day->getTotalDuration();
                        $userYear['years'][$yearId]['months'][$monthId]['totalDuration'] += $day->getTotalDuration();;
                    }
                    $userYears[] = $userYear;
                }
            }
            $rows = $userYears;
         */

        return $this->render('reporting/report_user_list_monthly.html.twig', [
            'report_title' => 'report_yearly_users',
            'box_id' => 'yearly-user-list-reporting-box',
            'form' => $form->createView(),
            'rows' => $rows,
            'months' => $months,
            'totals' => $totals,
        ]);
    }

    /**
     * @Route(path="/monthly_users_list", name="report_monthly_users", methods={"GET","POST"})
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

        $previous = clone $start;
        $previous->modify('-1 month');

        $next = clone $start;
        $next->modify('+1 month');

        foreach ($allUsers as $user) {
            $rows[] = [
                'days' => $this->timesheetRepository->getDailyStats($user, $start, $end),
                'user' => $user
            ];
        }

        $days = [];
        $totals = [];

        if (isset($rows[0])) {
            /** @var Day $day */
            foreach ($rows[0]['days'] as $day) {
                $days[$day->getDay()->format('Ymd')] = $day->getDay();
            }
            foreach ($rows as $row) {
                foreach ($row['days'] as $day) {
                    $totalsId = $day->getDay()->format('Ymd');
                    if (!isset($totals[$totalsId])) {
                        $totals[$totalsId] = 0;
                    }
                    $totals[$totalsId] += $day->getTotalDuration();
                }
            }
        }

        return $this->render('reporting/report_user_list.html.twig', [
            'report_title' => 'report_monthly_users',
            'box_id' => 'monthly-user-list-reporting-box',
            'form' => $form->createView(),
            'rows' => $rows,
            'days' => $days,
            'totals' => $totals,
            'current' => $start,
            'next' => $next,
            'previous' => $previous,
        ]);
    }

    /**
     * @Route(path="/weekly_users_list", name="report_weekly_users", methods={"GET","POST"})
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function weeklyUsersList(Request $request): Response
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory();
        $localeFormats = $this->getLocaleFormats($request->getLocale());

        $query = new UserQuery();
        $query->setCurrentUser($currentUser);
        $allUsers = $this->userRepository->getUsersForQuery($query);

        $rows = [];

        $values = new WeeklyUserList();
        $values->setDate($dateTimeFactory->getStartOfWeek());

        $form = $this->createForm(WeeklyUserListForm::class, $values, [
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
            'format' => $localeFormats->getDateTypeFormat(),
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

        foreach ($allUsers as $user) {
            $rows[] = [
                'days' => $this->timesheetRepository->getDailyStats($user, $start, $end),
                'user' => $user
            ];
        }

        $days = [];
        $totals = [];

        if (isset($rows[0])) {
            /** @var Day $day */
            foreach ($rows[0]['days'] as $day) {
                $days[$day->getDay()->format('Ymd')] = $day->getDay();
            }
            foreach ($rows as $row) {
                foreach ($row['days'] as $day) {
                    $totalsId = $day->getDay()->format('Ymd');
                    if (!isset($totals[$totalsId])) {
                        $totals[$totalsId] = 0;
                    }
                    $totals[$totalsId] += $day->getTotalDuration();
                }
            }
        }

        return $this->render('reporting/report_user_list.html.twig', [
            'report_title' => 'report_weekly_users',
            'box_id' => 'weekly-user-list-reporting-box',
            'form' => $form->createView(),
            'rows' => $rows,
            'days' => $days,
            'totals' => $totals,
            'current' => $start,
            'next' => $next,
            'previous' => $previous,
        ]);
    }
}
