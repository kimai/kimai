<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Controller\AbstractController;
use App\Model\Statistic\Day;
use App\Reporting\MonthlyUserList;
use App\Reporting\MonthlyUserListForm;
use App\Reporting\WeeklyUserList;
use App\Reporting\WeeklyUserListForm;
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
 * @Security("is_granted('view_reporting') and is_granted('view_other_timesheet')")
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

        return $this->render('reporting/report_user_list.html.twig', [
            'report_title' => 'report_monthly_users',
            'box_id' => 'monthly-user-list-reporting-box',
            'form' => $form->createView(),
            'rows' => $rows,
            'days' => $days,
            'current' => $start,
            'next' => $nextMonth,
            'previous' => $previousMonth,
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

        $previousWeek = clone $start;
        $previousWeek->modify('-1 week');

        $nextWeek = clone $start;
        $nextWeek->modify('+1 week');

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

        return $this->render('reporting/report_user_list.html.twig', [
            'report_title' => 'report_weekly_users',
            'box_id' => 'weekly-user-list-reporting-box',
            'form' => $form->createView(),
            'rows' => $rows,
            'days' => $days,
            'current' => $start,
            'next' => $nextWeek,
            'previous' => $previousWeek,
        ]);
    }
}
