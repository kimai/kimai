<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Timesheet;

use App\Entity\Bookmark;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Model\FavoriteTimesheet;
use App\Repository\BookmarkRepository;
use App\Repository\TimesheetRepository;

/**
 * @internal
 */
final class FavoriteRecordService
{
    public function __construct(private TimesheetRepository $repository, private BookmarkRepository $bookmarkRepository)
    {
    }

    /**
     * @param User $user
     * @param int $limit
     * @return array<FavoriteTimesheet>
     */
    public function favoriteEntries(User $user, int $limit = 5): array
    {
        $favIds = $this->getBookmark($user)->getContent();
        $recentIds = [];
        if (\count($favIds) < 5) {
            $recentIds = $this->repository->getRecentActivityIds($user, null, $limit);
        }
        $ids = \array_slice(array_unique(array_merge($favIds, $recentIds)), 0, $limit);

        $favorites = [];
        foreach ($ids as $id) {
            $favorites[$id] = \in_array($id, $favIds);
        }

        if (\count($ids) > 0) {
            $timesheets = $this->repository->findTimesheetsById($ids, false, false);
            foreach ($timesheets as $timesheet) {
                $favorites[$timesheet->getId()] = new FavoriteTimesheet($timesheet, $favorites[$timesheet->getId()]);
            }
        }

        return array_values($favorites);
    }

    private function getBookmark(User $user): Bookmark
    {
        $bookmark = $this->bookmarkRepository->findBookmark($user, 'favorite', 'recent');

        if ($bookmark === null) {
            $bookmark = new Bookmark();
            $bookmark->setUser($user);
            $bookmark->setType('favorite');
            $bookmark->setName('recent');
        }

        return $bookmark;
    }

    public function addFavorite(Timesheet $timesheet): void
    {
        if ($timesheet->getUser() === null) {
            throw new \InvalidArgumentException('Cannot favorite timesheet without user');
        }

        $bookmark = $this->getBookmark($timesheet->getUser());
        $ids = $bookmark->getContent();
        if (\in_array($timesheet->getId(), $ids)) {
            return;
        }

        if (\count($ids) >= 5) {
            array_pop($ids); // remove the last element and make space for a new id
        }
        array_unshift($ids, $timesheet->getId());
        $bookmark->setContent($ids);

        $this->bookmarkRepository->saveBookmark($bookmark);
    }

    public function removeFavorite(Timesheet $timesheet): void
    {
        if ($timesheet->getUser() === null) {
            throw new \InvalidArgumentException('Cannot favorite timesheet without user');
        }

        $bookmark = $this->getBookmark($timesheet->getUser());
        $ids = $bookmark->getContent();

        if (!\in_array($timesheet->getId(), $ids)) {
            return;
        }

        $newIds = [];
        foreach ($ids as $id) {
            if ($id !== $timesheet->getId()) {
                $newIds[] = $id;
            }
        }
        $bookmark->setContent($newIds);
        $this->bookmarkRepository->saveBookmark($bookmark);
    }
}
