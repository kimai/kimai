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
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\ActivityQuery;
use App\Repository\Query\CustomerQuery;
use App\Repository\Query\ProjectQuery;
use App\Repository\Query\UserQuery;
use App\Repository\UserRepository;
use App\Widget\Type\CompoundRow;
use App\Widget\Type\More;
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
            DashboardEvent::class => ['onDashboardEvent', 100],
        ];
    }

    /**
     * @param DashboardEvent $event
     */
    public function onDashboardEvent(DashboardEvent $event)
    {
        $user = $event->getUser();
        $section = new CompoundRow();
        $section->setTitle('');
        $section->setOrder(100);

        if ($this->security->isGranted('view_user')) {
            $query = new UserQuery();
            $query->setCurrentUser($user);
            $section->addWidget(
                (new More())
                    ->setId('userTotal')
                    ->setTitle('stats.userTotal')
                    ->setData($this->user->countUsersForQuery($query))
                    ->setOptions([
                        'route' => 'admin_user',
                        'icon' => 'user',
                        'color' => 'primary',
                    ])
            );
        }

        if ($this->security->isGranted('view_customer')) {
            $query = new CustomerQuery();
            $query->setCurrentUser($user);
            $section->addWidget(
                (new More())
                    ->setId('customerTotal')
                    ->setTitle('stats.customerTotal')
                    ->setData($this->customer->countCustomersForQuery($query))
                    ->setOptions([
                        'route' => 'admin_customer',
                        'icon' => 'customer',
                        'color' => 'primary',
                    ])
            );
        }

        if ($this->security->isGranted('view_project')) {
            $query = new ProjectQuery();
            $query->setCurrentUser($user);
            $section->addWidget(
                (new More())
                    ->setId('projectTotal')
                    ->setTitle('stats.projectTotal')
                    ->setData($this->project->countProjectsForQuery($query))
                    ->setOptions([
                        'route' => 'admin_project',
                        'icon' => 'project',
                        'color' => 'primary',
                    ])
            );
        }

        if ($this->security->isGranted('view_activity')) {
            $query = new ActivityQuery();
            $query->setCurrentUser($user);
            $section->addWidget(
                (new More())
                    ->setId('activityTotal')
                    ->setTitle('stats.activityTotal')
                    ->setData($this->activity->countActivitiesForQuery($query))
                    ->setOptions([
                        'route' => 'admin_activity',
                        'icon' => 'activity',
                        'color' => 'primary',
                    ])
            );
        }

        if (\count($section->getWidgets()) > 0) {
            $event->addSection($section);
        }
    }
}
