<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting;

use App\Entity\User;
use App\Event\ReportingEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ReportingService
{
    public const DEFAULT_VIEW = 'week_by_user';

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $security;

    public function __construct(EventDispatcherInterface $dispatcher, AuthorizationCheckerInterface $security)
    {
        $this->dispatcher = $dispatcher;
        $this->security = $security;
    }

    /**
     * @param User $user
     * @return ReportInterface[]
     */
    public function getAvailableReports(User $user): array
    {
        $event = new ReportingEvent($user);

        if ($this->security->isGranted('view_reporting')) {
            $event->addReport(new Report('week_by_user', 'report_user_week', 'report_user_week', 'user'));
            $event->addReport(new Report('month_by_user', 'report_user_month', 'report_user_month', 'user'));
            if ($this->security->isGranted('view_other_timesheet')) {
                $event->addReport(new Report('weekly_users_list', 'report_weekly_users', 'report_weekly_users', 'user'));
                $event->addReport(new Report('monthly_users_list', 'report_monthly_users', 'report_monthly_users', 'user'));
            }
            if ($this->security->isGranted('budget_project')) {
                $event->addReport(new Report('project_view', 'report_project_view', 'report_project_view', 'project'));
                $event->addReport(new Report('inactive_projects', 'report_project_inactive', 'report_inactive_project', 'project'));
            }

            $this->dispatcher->dispatch($event);
        }

        return $event->getReports();
    }
}
