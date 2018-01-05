<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Model\UserStatistic;
use TimesheetBundle\Entity\Activity;
use TimesheetBundle\Entity\Project;
use TimesheetBundle\Entity\Timesheet;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use TimesheetBundle\Model\ActivityStatistic;
use TimesheetBundle\Model\ProjectStatistic;
use TimesheetBundle\Model\TimesheetGlobalStatistic;
use TimesheetBundle\Model\TimesheetStatistic;

/**
 * Dashboard controller for the admin area.
 *
 * @Route("/dashboard")
 * @Security("has_role('ROLE_USER')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class DashboardController extends Controller
{
    /**
     * @Route("/", defaults={}, name="dashboard")
     * @Method("GET")
     */
    public function indexAction()
    {
        $user = $this->getUser();

        $timesheetRepo = $this->getDoctrine()->getRepository(Timesheet::class);
        $timesheetUserStats = $timesheetRepo->getUserStatistics($user);
        $timesheetGlobalStats = $timesheetRepo->getGlobalStatistics();

        $activityStats = $this->getDoctrine()->getRepository(Activity::class)->getGlobalStatistics();
        $projectStats = $this->getDoctrine()->getRepository(Project::class)->getGlobalStatistics();
        $userStats = $this->getDoctrine()->getRepository(User::class)->getGlobalStatistics();

        return $this->render('dashboard/index.html.twig', [
            'dashboard_widgets' => $this->getWidgets($timesheetUserStats, $timesheetGlobalStats, $activityStats, $projectStats, $userStats),
            'timesheetGlobal' => $timesheetGlobalStats,
            'timesheetUser' => $timesheetUserStats,
            'activity' => $activityStats,
            'project' => $projectStats,
            'user' => $userStats,
        ]);
    }

    protected function getWidgets(
        TimesheetStatistic $timesheetUserStats,
        TimesheetGlobalStatistic $timesheetGlobalStats,
        ActivityStatistic $activityStats,
        ProjectStatistic $projectStats,
        UserStatistic $userStats
    ) {
        $widgets = [
            /*
            [
                'header' => 'dashboard.you',
                'widgets' => [
                    "{{ widgets.info_box_progress('Bewilligte Stunden', 'Stunden zur Abrechnung bewilligt', 120, 10, 'star-o') }}",
                    "{{ widgets.info_box_progress('Umsatz / Monat', '70% Increase in 30 Days', 6830, 30, 'credit-card', 'black') }}",
                    "{{ widgets.info_box_progress('Stunden persönlich', 'Das ist noch nicht genug', 135, 60, 'hourglass-o') }}",
                    "{{ widgets.info_box_progress('Anzahl Benutzer', 'Mehr ist besser!', 5, 90, 'user') }}",
                ],
            ],
            */
            [
                'id' => 'profile.stats',
                'header' => 'dashboard.you',
                'widgets' => [
                    "{{ widgets.info_box_counter('stats.durationThisMonth', timesheetUser.durationThisMonth|duration(true), 'hourglass-o', 'green') }}",
                    //"{{ widgets.info_box_counter('stats.amountThisMonth', timesheetUser.amountThisMonth|money, 'money', 'blue') }}",
                    "{{ widgets.info_box_counter('stats.durationTotal', timesheetUser.durationTotal|duration(true), 'hourglass-o', 'red') }}",
                    //"{{ widgets.info_box_counter('stats.amountTotal', timesheetUser.amountTotal|money, 'money', 'yellow') }}",
                ],
            ],
        ];

        if (!$this->isGranted('ROLE_TEAMLEAD', null)) {
            return $widgets;
        }

        $widgets[] = [
            'id' => 'alluser.stats',
            'header' => 'dashboard.all',
            'widgets' => [
                "{{ widgets.info_box_counter('stats.durationThisMonth', timesheetGlobal.durationThisMonth|duration(true), 'hourglass-o', 'blue') }}",
                //"{{ widgets.info_box_counter('stats.amountThisMonth', timesheetGlobal.amountThisMonth|money, 'money', 'green') }}",
                "{{ widgets.info_box_counter('stats.durationTotal', timesheetGlobal.durationTotal|duration(true), 'hourglass-o', 'yellow') }}",
                //"{{ widgets.info_box_counter('stats.amountTotal', timesheetGlobal.amountTotal|money, 'money', 'red') }}",
            ],
        ];

        $widgets[] = [
            'id' => 'user.stats',
            'header' => '',
            'widgets' => [
                "{{ widgets.info_box_counter('stats.userTotal', user.totalAmount, 'users', 'red') }}",
                "{{ widgets.info_box_counter('stats.userActiveThisMoth', timesheetGlobal.activeThisMonth, 'users', 'yellow') }}",
                "{{ widgets.info_box_counter('stats.userActiveEver', timesheetGlobal.activeTotal, 'users', 'blue') }}",
                "{{ widgets.info_box_counter('stats.userActiveNow', timesheetGlobal.activeCurrently, 'users', 'green') }}",
            ],
        ];

        if (!$this->isGranted('ROLE_ADMIN', null)) {
            return $widgets;
        }

        $widgets[] = [
            'id' => 'admin.stats',
            'header' => 'dashboard.admin',
            'widgets' => [
                "{{ widgets.info_box_more('stats.activitiesTotal', activity.totalAmount, '', path('admin_activity'), 'tasks', 'purple') }}",
                "{{ widgets.info_box_more('stats.projectsTotal', project.totalAmount, '', path('admin_project'), 'book', 'yellow') }}",
                "{{ widgets.info_box_more('stats.userTotal', user.totalAmount, ' ', path('admin_user'), 'users') }}",
                "{{ widgets.info_box_more('stats.userActiveNow', timesheetGlobal.activeCurrently, '', path('admin_timesheet', {'state': 1}), 'hourglass-o', 'red') }}", // FIXME ???
            ],
        ];

        return $widgets;
    }
}
