<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Model\DailyStatistic;
use App\Model\Statistic\StatisticDate;
use App\Reporting\MonthByUser;
use App\Reporting\MonthByUserForm;
use App\Reporting\WeekByUser;
use App\Reporting\WeekByUserForm;
use App\Repository\ActivityRepository;
use App\Repository\ProjectRepository;
use App\Timesheet\TimesheetStatisticService;
use DateTime;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route(path="/reporting")
 * @Security("is_granted('view_reporting')")
 */
final class ReportByUserController extends AbstractController
{
    private $statisticService;
    private $projectRepository;
    private $activityRepository;

    public function __construct(TimesheetStatisticService $statisticService, ProjectRepository $projectRepository, ActivityRepository $activityRepository)
    {
        $this->statisticService = $statisticService;
        $this->projectRepository = $projectRepository;
        $this->activityRepository = $activityRepository;
    }

    private function canSelectUser(): bool
    {
        // also found in App\EventSubscriber\Actions\UserSubscriber
        if (!$this->isGranted('view_other_timesheet') || !$this->isGranted('view_other_reporting')) {
            return false;
        }

        return true;
    }

    /**
     * @Route(path="/month_by_user", name="report_user_month", methods={"GET","POST"})
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function monthByUser(Request $request): Response
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory($currentUser);
        $canChangeUser = $this->canSelectUser();

        $values = new MonthByUser();
        $values->setUser($currentUser);
        $values->setDate($dateTimeFactory->getStartOfMonth());
        $values->setSumType('duration');

        $form = $this->createForm(MonthByUserForm::class, $values, [
            'include_user' => $canChangeUser,
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
        ]);

        $form->submit($request->query->all(), false);

        if ($values->getUser() === null) {
            $values->setUser($currentUser);
        }

        if ($currentUser !== $values->getUser() && !$canChangeUser) {
            throw new AccessDeniedException('User is not allowed to see other users timesheet');
        }

        if ($values->getDate() === null) {
            $values->setDate($dateTimeFactory->getStartOfMonth());
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

        $data = $this->prepareReport($start, $end, $selectedUser);

        return $this->render('reporting/report_by_user.html.twig', [
            'report_title' => 'report_user_month',
            'box_id' => 'user-month-reporting-box',
            'form' => $form->createView(),
            'rows' => $data,
            'days' => new DailyStatistic($start, $end, $selectedUser),
            'user' => $selectedUser,
            'current' => $start,
            'next' => $nextMonth,
            'previous' => $previousMonth,
        ]);
    }

    /**
     * @Route(path="/week_by_user", name="report_user_week", methods={"GET","POST"})
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function weekByUser(Request $request): Response
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory($currentUser);
        $canChangeUser = $this->canSelectUser();

        $values = new WeekByUser();
        $values->setUser($currentUser);
        $values->setDate($dateTimeFactory->getStartOfWeek());
        $values->setSumType('duration');

        $form = $this->createForm(WeekByUserForm::class, $values, [
            'include_user' => $canChangeUser,
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
        ]);

        $form->submit($request->query->all(), false);

        if ($values->getUser() === null) {
            $values->setUser($currentUser);
        }

        if ($currentUser !== $values->getUser() && !$canChangeUser) {
            throw new AccessDeniedException('User is not allowed to see other users timesheet');
        }

        if ($values->getDate() === null) {
            $values->setDate($dateTimeFactory->getStartOfWeek());
        }

        $start = $dateTimeFactory->getStartOfWeek($values->getDate());
        $end = $dateTimeFactory->getEndOfWeek($values->getDate());
        $selectedUser = $values->getUser();

        $previous = clone $start;
        $previous->modify('-1 week');

        $next = clone $start;
        $next->modify('+1 week');

        $data = $this->prepareReport($start, $end, $selectedUser);

        return $this->render('reporting/report_by_user.html.twig', [
            'report_title' => 'report_user_week',
            'box_id' => 'user-week-reporting-box',
            'form' => $form->createView(),
            'days' => new DailyStatistic($start, $end, $selectedUser),
            'rows' => $data,
            'user' => $selectedUser,
            'current' => $start,
            'next' => $next,
            'previous' => $previous,
        ]);
    }

    private function prepareReport(DateTime $begin, DateTime $end, User $user): array
    {
        $data = $this->statisticService->getDailyStatisticsGrouped($begin, $end, [$user]);

        $data = array_pop($data);
        $projectIds = [];
        $activityIds = [];

        foreach ($data as $projectId => $projectValues) {
            $projectIds[$projectId] = $projectId;
            $dailyProjectStatistic = new DailyStatistic($begin, $end, $user);
            foreach ($projectValues['activities'] as $activityId => $activityValues) {
                $activityIds[$activityId] = $activityId;
                if (!isset($data[$projectId]['duration'])) {
                    $data[$projectId]['duration'] = 0;
                }
                if (!isset($data[$projectId]['rate'])) {
                    $data[$projectId]['rate'] = 0;
                }
                if (!isset($data[$projectId]['internalRate'])) {
                    $data[$projectId]['internalRate'] = 0;
                }
                if (!isset($data[$projectId]['activities'][$activityId]['duration'])) {
                    $data[$projectId]['activities'][$activityId]['duration'] = 0;
                }
                if (!isset($data[$projectId]['activities'][$activityId]['rate'])) {
                    $data[$projectId]['activities'][$activityId]['rate'] = 0;
                }
                if (!isset($data[$projectId]['activities'][$activityId]['internalRate'])) {
                    $data[$projectId]['activities'][$activityId]['internalRate'] = 0;
                }
                /** @var StatisticDate $day */
                foreach ($activityValues['days']->getDays() as $day) {
                    $statDay = $dailyProjectStatistic->getDayByDateTime($day->getDate());
                    $statDay->setTotalDuration($statDay->getTotalDuration() + $day->getDuration());
                    $statDay->setTotalRate($statDay->getTotalRate() + $day->getRate());
                    $statDay->setTotalInternalRate($statDay->getTotalInternalRate() + $day->getInternalRate());
                    $data[$projectId]['duration'] = $data[$projectId]['duration'] + $day->getDuration();
                    $data[$projectId]['rate'] = $data[$projectId]['rate'] + $day->getRate();
                    $data[$projectId]['internalRate'] = $data[$projectId]['internalRate'] + $day->getInternalRate();
                    $data[$projectId]['activities'][$activityId]['duration'] = $data[$projectId]['activities'][$activityId]['duration'] + $day->getDuration();
                    $data[$projectId]['activities'][$activityId]['rate'] = $data[$projectId]['activities'][$activityId]['rate'] + $day->getRate();
                    $data[$projectId]['activities'][$activityId]['internalRate'] = $data[$projectId]['activities'][$activityId]['internalRate'] + $day->getInternalRate();
                }
            }
            $data[$projectId]['days'] = $dailyProjectStatistic;
        }

        $activities = $this->activityRepository->findByIds($activityIds);
        foreach ($activities as $activity) {
            $activityIds[$activity->getId()] = $activity;
        }

        foreach ($data as $projectId => $projectValues) {
            foreach ($projectValues['activities'] as $activityId => $activityValues) {
                $data[$projectId]['activities'][$activityId]['activity'] = $activityIds[$activityId];
            }
        }

        $projects = $this->projectRepository->findByIds($projectIds);
        foreach ($projects as $project) {
            $data[$project->getId()]['project'] = $project;
        }

        return $data;
    }
}
