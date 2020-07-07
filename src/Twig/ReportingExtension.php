<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Entity\User;
use App\Event\ReportingEvent;
use App\Reporting\Report;
use App\Reporting\ReportInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ReportingExtension extends AbstractExtension
{
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
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('available_reports', [$this, 'getAvailableReports'], []),
        ];
    }

    /**
     * @param User $user
     * @return ReportInterface[]
     */
    public function getAvailableReports(User $user): array
    {
        $event = new ReportingEvent($user);

        if ($this->security->isGranted('view_reporting')) {
            $event->addReport(new Report('month_by_user', 'report_user_month', 'report_user_month'));
            if ($this->security->isGranted('view_other_timesheet')) {
                $event->addReport(new Report('monthly_users_list', 'report_monthly_users', 'report_monthly_users'));
            }
        }

        $this->dispatcher->dispatch($event);

        return $event->getReports();
    }
}
