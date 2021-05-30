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
use App\Reporting\MonthByUser;
use App\Reporting\MonthByUserForm;
use App\Reporting\WeekByUser;
use App\Reporting\WeekByUserForm;
use App\Repository\TimesheetRepository;
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
    /**
     * @var TimesheetRepository
     */
    private $timesheetRepository;

    public function __construct(TimesheetRepository $timesheetRepository)
    {
        $this->timesheetRepository = $timesheetRepository;
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
        $localeFormats = $this->getLocaleFormats($request->getLocale());
        $canChangeUser = $this->canSelectUser();

        $values = new MonthByUser();
        $values->setUser($currentUser);
        $values->setDate($dateTimeFactory->getStartOfMonth());

        $form = $this->createForm(MonthByUserForm::class, $values, [
            'include_user' => $canChangeUser,
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
            'format' => $localeFormats->getDateTypeFormat(),
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

        $data = $this->timesheetRepository->getDailyStats($selectedUser, $start, $end);
        $rows = $this->prepareReportData($data);

        return $this->render('reporting/report_by_user.html.twig', [
            'report_title' => 'report_user_month',
            'box_id' => 'user-month-reporting-box',
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
        $localeFormats = $this->getLocaleFormats($request->getLocale());
        $canChangeUser = $this->canSelectUser();

        $values = new WeekByUser();
        $values->setUser($currentUser);
        $values->setDate($dateTimeFactory->getStartOfWeek());

        $form = $this->createForm(WeekByUserForm::class, $values, [
            'include_user' => $canChangeUser,
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
            'format' => $localeFormats->getDateTypeFormat(),
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

        $data = $this->timesheetRepository->getDailyStats($selectedUser, $start, $end);
        $rows = $this->prepareReportData($data);

        return $this->render('reporting/report_by_user.html.twig', [
            'report_title' => 'report_user_week',
            'box_id' => 'user-week-reporting-box',
            'form' => $form->createView(),
            'days' => $data,
            'rows' => $rows,
            'user' => $selectedUser,
            'current' => $start,
            'next' => $next,
            'previous' => $previous,
        ]);
    }

    /**
     * @param Day[] $data
     * @return array
     */
    private function prepareReportData(array $data): array
    {
        $days = [];

        foreach ($data as $day) {
            $days[$day->getDay()->format('Ymd')] = ['date' => $day->getDay(), 'duration' => 0];
        }

        $rows = [];

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
