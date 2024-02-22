<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\FavoriteTimesheet;
use App\Repository\TimesheetRepository;
use App\Timesheet\FavoriteRecordService;
use Twig\Extension\RuntimeExtensionInterface;

final class TimesheetExtension implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly TimesheetRepository $repository,
        private readonly FavoriteRecordService $favoriteRecordService
    )
    {
    }

    /**
     * @return array<Timesheet>
     */
    public function activeEntries(User $user, bool $ticktack = true): array
    {
        return $this->repository->getActiveEntries($user, $ticktack);
    }

    /**
     * @return array<FavoriteTimesheet>
     */
    public function favoriteEntries(User $user, int $limit = 5): array
    {
        return $this->favoriteRecordService->favoriteEntries($user, $limit);
    }
}
