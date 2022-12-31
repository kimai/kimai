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
    public function __construct(private EventDispatcherInterface $dispatcher, private AuthorizationCheckerInterface $security)
    {
    }

    /**
     * @param User $user
     * @return ReportInterface[]
     */
    public function getAvailableReports(User $user): array
    {
        $event = new ReportingEvent($user);

        if ($this->security->isGranted('view_reporting')) {
            if ($this->security->isGranted('report:user')) {
                $event->addReport(new Report('week_by_user', 'report_user_week', 'report_user_week', 'user'));
                $event->addReport(new Report('month_by_user', 'report_user_month', 'report_user_month', 'user'));
                $event->addReport(new Report('year_by_user', 'report_user_year', 'report_user_year', 'user'));
            }

            if ($viewOther = $this->security->isGranted('report:other')) {
                $event->addReport(new Report('weekly_users_list', 'report_weekly_users', 'report_weekly_users', 'users'));
                $event->addReport(new Report('monthly_users_list', 'report_monthly_users', 'report_monthly_users', 'users'));
                $event->addReport(new Report('yearly_users_list', 'report_yearly_users', 'report_yearly_users', 'users'));
            }

            if ($this->security->isGranted('report:project')) {
                if ($this->security->isGranted('details', 'project')) {
                    $event->addReport(new Report('project_details', 'report_project_details', 'report_project_details', 'project'));
                }
                if ($this->security->isGranted('budget_any', 'project')) {
                    $event->addReport(new Report('project_view', 'report_project_view', 'report_project_view', 'project'));
                    $event->addReport(new Report('daterange_projects', 'report_project_daterange', 'report_project_daterange', 'project'));
                    $event->addReport(new Report('inactive_projects', 'report_project_inactive', 'report_inactive_project', 'project'));
                }
            }

            if ($this->security->isGranted('report:customer')) {
                if ($viewOther) {
                    $event->addReport(new Report('report_customer_monthly_projects', 'report_customer_monthly_projects', 'report_customer_monthly_projects', 'customer'));
                }
            }

            $this->dispatcher->dispatch($event);
        }

        return $event->getReports();
    }
}
