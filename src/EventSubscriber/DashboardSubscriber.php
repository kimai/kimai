<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use App\Event\DashboardEvent;
use App\Model\DashboardSection;
use App\Model\Widget;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Used to add Dashboard widgets for users with ROLE_ADMIN.
 */
class DashboardSubscriber implements EventSubscriberInterface
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $security;
    /**
     * @var UserRepository
     */
    protected $user;
    /**
     * @var ActivityRepository
     */
    protected $activity;
    /**
     * @var ProjectRepository
     */
    protected $project;
    /**
     * @var CustomerRepository
     */
    protected $customer;

    /**
     * @param AuthorizationCheckerInterface $security
     * @param UserRepository $user
     * @param ActivityRepository $activity
     * @param ProjectRepository $project
     * @param CustomerRepository $customer
     */
    public function __construct(
        AuthorizationCheckerInterface $security,
        UserRepository $user,
        ActivityRepository $activity,
        ProjectRepository $project,
        CustomerRepository $customer
    ) {
        $this->security = $security;
        $this->user = $user;
        $this->activity = $activity;
        $this->project = $project;
        $this->customer = $customer;
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
        if (!$this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        $this->addAdminWidgets($event);
    }

    /**
     * @param DashboardEvent $event
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function addAdminWidgets(DashboardEvent $event)
    {
        $section = new DashboardSection('dashboard.admin');
        $section->setOrder(100);

        $widget = new Widget('stats.userTotal', $this->user->countUser());
        $widget
            ->setRoute('admin_user')
            ->setIcon('user')
            ->setType(Widget::TYPE_MORE)
        ;
        $section->addWidget($widget);

        $widget = new Widget('stats.customerTotal', $this->customer->countCustomer());
        $widget
            ->setRoute('admin_customer')
            ->setIcon('customer')
            ->setColor('blue')
            ->setType(Widget::TYPE_MORE)
        ;
        $section->addWidget($widget);

        $widget = new Widget('stats.projectTotal', $this->project->countProject());
        $widget
            ->setRoute('admin_project')
            ->setIcon('project')
            ->setColor('yellow')
            ->setType(Widget::TYPE_MORE)
        ;
        $section->addWidget($widget);

        $widget = new Widget('stats.activityTotal', $this->activity->countActivity());
        $widget
            ->setRoute('admin_activity')
            ->setIcon('activity')
            ->setColor('purple')
            ->setType(Widget::TYPE_MORE)
        ;
        $section->addWidget($widget);

        $event->addSection($section);
    }
}
