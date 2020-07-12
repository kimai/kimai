<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Model\Statistic\Day;
use App\Reporting\MonthByUser;
use App\Reporting\MonthByUserForm;
use App\Reporting\MonthlyUserList;
use App\Reporting\MonthlyUserListForm;
use App\Repository\Query\UserQuery;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use App\Timesheet\UserDateTimeFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
    /**
     * @var UserDateTimeFactory
     */
    private $dateTimeFactory;

    public function __construct(TimesheetRepository $timesheetRepository, UserRepository $userRepository, UserDateTimeFactory $dateTimeFactory)
    {
        $this->timesheetRepository = $timesheetRepository;
        $this->userRepository = $userRepository;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    /**
     * @Route(path="/", name="reporting", methods={"GET"})
     * @Route(path="/month_by_user", name="report_user_month", methods={"GET","POST"})
     */
    public function monthByUser(Request $request)
    {
        $user = $this->getUser();

        $values = new MonthByUser();
        $values->setUser($user);
        $values->setDate($this->dateTimeFactory->getStartOfMonth());

        $form = $this->createForm(MonthByUserForm::class, $values, [
            'method' => 'POST',
            'include_user' => $this->isGranted('view_other_timesheet') && $user->hasTeamAssignment(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $values->setUser($user);
            $values->setDate($this->dateTimeFactory->getStartOfMonth());
        }

        if ($user !== $values->getUser() && !$this->isGranted('view_other_timesheet')) {
            throw new AccessDeniedException('User is not allowed to see other users timesheet');
        }

        $start = $values->getDate();
        $start->modify('first day of 00:00:00');

        $end = clone $start;
        $end->modify('last day of 23:59:59');

        $selectedUser = $values->getUser();

        $previousMonth = clone $start;
        $previousMonth->modify('-1 month');

        $nextMonth = clone $start;
        $nextMonth->modify('+1 month');

        $data = $this->timesheetRepository->getDailyStats($selectedUser, $start, $end);
        $rows = $this->prepareMonthlyData($data);

        return $this->render('reporting/month_by_user.html.twig', [
            'form' => $form->createView(),
            'days' => $data,
            'rows' => $rows,
            'user' => $selectedUser,
            'current' => $start,
            'next' => $nextMonth,
            'previous' => $previousMonth,
        ]);
    }

    /**
     * @Route(path="/monthly_users_list", name="report_monthly_users", methods={"GET","POST"})
     * @Security("is_granted('view_other_timesheet')")
     */
    public function montlyhUsersList(Request $request)
    {
        $currentUser = $this->getUser();

        $query = new UserQuery();
        $query->setCurrentUser($currentUser);
        $allUsers = $this->userRepository->getUsersForQuery($query);

        $rows = [];

        $values = new MonthlyUserList();
        $values->setDate($this->dateTimeFactory->getStartOfMonth());

        $form = $this->createForm(MonthlyUserListForm::class, $values, [
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $values->setDate($this->dateTimeFactory->getStartOfMonth());
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

    private function prepareMonthlyData(array $data): array
    {
        $days = [];

        foreach ($data as $day) {
            $days[$day->getDay()->format('Ymd')] = ['date' => $day->getDay(), 'duration' => 0];
        }

        $rows = [];

        /** @var Day $day */
        foreach ($data as $day) {
            $dayId = $day->getDay()->format('Ymd');
            foreach ($day->getDetails() as $id => $detail) {
                $projectId = $detail['project']->getId();
                if (!\array_key_exists($projectId, $rows)) {
                    $rows[$projectId] = [
                        'project' => $detail['project'],
                        'duration' => 0,
                        'days' => $days,
                        'activities' => [],
                    ];
                }

                $rows[$projectId]['duration'] += $detail['duration'];
                $rows[$projectId]['days'][$dayId]['duration'] += $detail['duration'];

                $activityId = $detail['activity']->getId();
                if (!\array_key_exists($activityId, $rows[$projectId]['activities'])) {
                    $rows[$projectId]['activities'][$activityId] = [
                        'activity' => $detail['activity'],
                        'duration' => 0,
                        'days' => $days,
                    ];
                }

                $rows[$projectId]['activities'][$activityId]['duration'] += $detail['duration'];
                $rows[$projectId]['activities'][$activityId]['days'][$dayId]['duration'] += $detail['duration'];
            }
        }

        return $rows;
    }
}
