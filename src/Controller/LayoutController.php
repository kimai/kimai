<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\SystemConfiguration;
use App\Event\RecentActivityEvent;
use App\Repository\TimesheetRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Used for the (initial) page rendering.
 */
class LayoutController extends AbstractController
{
    public function activeEntries(TimesheetRepository $repository, SystemConfiguration $configuration, EventDispatcherInterface $dispatcher): Response
    {
        $user = $this->getUser();
        $activeEntries = $repository->getActiveEntries($user);

        $recentActivity = new RecentActivityEvent($this->getUser(), $activeEntries);
        $dispatcher->dispatch($recentActivity);

        return $this->render(
            'navbar/active-entries.html.twig',
            [
                'entries' => $recentActivity->getRecentActivities(),
                'soft_limit' => $configuration->getTimesheetActiveEntriesSoftLimit(),
            ]
        );
    }
}
