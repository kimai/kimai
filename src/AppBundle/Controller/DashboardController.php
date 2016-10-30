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
use TimesheetBundle\Entity\Activity;
use TimesheetBundle\Entity\Project;
use TimesheetBundle\Entity\Timesheet;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

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
            'timesheetGlobal' => $timesheetGlobalStats,
            'timesheetUser' => $timesheetUserStats,
            'activity' => $activityStats,
            'project' => $projectStats,
            'user' => $userStats,
        ]);
    }
}
