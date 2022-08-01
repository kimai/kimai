<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Entity\User;
use App\Event\RecentActivityEvent;
use App\Model\FavoriteTimesheet;
use App\Repository\TimesheetRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

final class FavoriteRecordService
{
    public function __construct(private TimesheetRepository $repository, private EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * @param User $user
     * @param int $limit
     * @return array<FavoriteTimesheet>
     */
    public function favoriteEntries(User $user, int $limit = 5): array
    {
        // TODO add favorite records to list
        $favorites = [];

        $data = $this->repository->getRecentActivities($user, null, $limit);
        $recentActivity = new RecentActivityEvent($user, $data);
        $this->eventDispatcher->dispatch($recentActivity);

        foreach ($recentActivity->getRecentActivities() as $recentActivity) {
            $favorites[] = new FavoriteTimesheet($recentActivity, false);
        }

        return $favorites;
    }
}
