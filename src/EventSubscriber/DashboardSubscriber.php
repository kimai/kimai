<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Event\DashboardEvent;
use App\Model\TimesheetGlobalStatistic;
use App\Model\TimesheetStatistic;
use App\Model\UserStatistic;
use App\Model\WidgetRow;
use App\Repository\Query\TimesheetQuery;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Used to add Dashboard widgets for a user.
 */
class DashboardSubscriber implements EventSubscriberInterface
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $security;
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * MenuSubscriber constructor.
     * @param AuthorizationCheckerInterface $security
     */
    public function __construct(AuthorizationCheckerInterface $security, ManagerRegistry $registry)
    {
        $this->security = $security;
        $this->registry = $registry;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            DashboardEvent::DASHBOARD => ['onDashboardEvent', 100],
        ];
    }

    /**
     * @param DashboardEvent $event
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function onDashboardEvent(DashboardEvent $event)
    {
        $timesheetRepo = $this->registry->getRepository(Timesheet::class);
        $timesheetGlobal = $timesheetRepo->getGlobalStatistics();
        $timesheetUser = $timesheetRepo->getUserStatistics($event->getUser());
        $userStats = $this->registry->getRepository(User::class)->getGlobalStatistics();

        $this->addUserWidgets($event, $timesheetUser);

        if (!$this->security->isGranted('ROLE_TEAMLEAD')) {
            return;
        }

        $this->addTeamleadWidgets($event, $timesheetGlobal, $userStats);

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $this->addAdminWidgets($event, $timesheetGlobal, $userStats);
    }

    /**
     * @param DashboardEvent $event
     * @param TimesheetStatistic $timesheet
     */
    protected function addUserWidgets(DashboardEvent $event, TimesheetStatistic $timesheet)
    {
        /*
        $row = new WidgetRow('dashboard.you');
        $widgets = [
            [
                'widgets' => [
                    "{{ widgets.info_box_progress('Bewilligte Stunden', 'Stunden zur Abrechnung bewilligt', 120, 10, 'star') }}",
                    "{{ widgets.info_box_progress('Umsatz / Monat', '70% Increase in 30 Days', 6830, 30, 'credit-card', 'black') }}",
                    "{{ widgets.info_box_progress('Stunden persönlich', 'Das ist noch nicht genug', 135, 60, 'hourglass') }}",
                    "{{ widgets.info_box_progress('Anzahl Benutzer', 'Mehr ist besser!', 5, 90, 'user') }}",
                ],
            ],
        $event->addWidgetRow($row);
        */

        $row = new WidgetRow('profile.stats', 'dashboard.you');
        $row
            ->add("{{ widgets.info_box_counter('stats.durationThisMonth', " . $timesheet->getDurationThisMonth() . "|duration(true), 'far fa-hourglass', 'green') }}")
            //->add("{{ widgets.info_box_counter('stats.amountThisMonth', ".$timesheet->getAmountThisMonth()."|money, 'money', 'blue') }}")
            ->add("{{ widgets.info_box_counter('stats.durationTotal', " . $timesheet->getDurationTotal() . "|duration(true), 'far fa-hourglass', 'red') }}")
            //->add("{{ widgets.info_box_counter('stats.amountTotal', ".$timesheet->getAmountTotal()."|money, 'money', 'yellow') }}")
        ;
        $event->addWidgetRow($row);
    }

    /**
     * @param DashboardEvent $event
     * @param TimesheetGlobalStatistic $timesheet
     * @param UserStatistic $userStats
     */
    protected function addTeamleadWidgets(DashboardEvent $event, TimesheetGlobalStatistic $timesheet, UserStatistic $userStats)
    {
        $row = new WidgetRow('alluser.stats', 'dashboard.all');
        $row
            ->add("{{ widgets.info_box_counter('stats.durationThisMonth', " . $timesheet->getDurationThisMonth() . "|duration(true), 'far fa-hourglass', 'blue') }}")
            ->add("{{ widgets.info_box_counter('stats.durationTotal', " . $timesheet->getDurationTotal() . "|duration(true), 'far fa-hourglass', 'yellow') }}")
            ->add("{{ widgets.info_box_counter('stats.activeRecordings', " . $timesheet->getActiveCurrently() . ", 'far fa-hourglass', 'red', path('admin_timesheet', {'state': " . TimesheetQuery::STATE_RUNNING . '})) }}')
        ;
        $event->addWidgetRow($row);

        $row = new WidgetRow('user.stats');
        $row
            ->add("{{ widgets.info_box_counter('stats.userTotal', " . $userStats->getTotalAmount() . ", 'user', 'red') }}")
            ->add("{{ widgets.info_box_counter('stats.userActiveThisMoth', " . $timesheet->getActiveThisMonth() . ", 'user', 'yellow') }}")
            ->add("{{ widgets.info_box_counter('stats.userActiveEver', " . $timesheet->getActiveTotal() . ", 'user', 'blue') }}")
        ;
        $event->addWidgetRow($row);
    }

    /**
     * @param DashboardEvent $event
     * @param TimesheetGlobalStatistic $timesheet
     * @param UserStatistic $user
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function addAdminWidgets(DashboardEvent $event, TimesheetGlobalStatistic $timesheet, UserStatistic $user)
    {
        $row = new WidgetRow('alluser.money_stats');
        $row
            ->add("{{ widgets.info_box_counter('stats.amountThisMonth', " . $timesheet->getAmountThisMonth() . "|money, 'far fa-money-bill-alt', 'green') }}")
            ->add("{{ widgets.info_box_counter('stats.amountTotal', " . $timesheet->getAmountTotal() . "|money, 'far fa-money-bill-alt', 'red') }}")
        ;
        $event->addWidgetRow($row);

        $activity = $this->registry->getRepository(Activity::class)->getGlobalStatistics();
        $project = $this->registry->getRepository(Project::class)->getGlobalStatistics();
        $customer = $this->registry->getRepository(Customer::class)->getGlobalStatistics();

        $row = new WidgetRow('admin.stats', 'dashboard.admin');
        $row
            ->add("{{ widgets.info_box_more('stats.userTotal', " . $user->getTotalAmount() . ", ' ', path('admin_user'), 'user') }}")
            ->add("{{ widgets.info_box_more('stats.customerTotal', " . $customer->getCount() . ", '', path('admin_customer'), 'customer', 'blue') }}")
            ->add("{{ widgets.info_box_more('stats.projectsTotal', " . $project->getCount() . ", '', path('admin_project'), 'project', 'yellow') }}")
            ->add("{{ widgets.info_box_more('stats.activitiesTotal', " . $activity->getCount() . ", '', path('admin_activity'), 'activity', 'purple') }}")
        ;
        $event->addWidgetRow($row);
    }
}
