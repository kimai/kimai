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
    public const MAX_FAVORITES = 5;

    public function __construct(private TimesheetRepository $repository, private BookmarkRepository $bookmarkRepository)
    {
    }

    /**
     * Returns the favorites of the user, filled up with recently used records to be offered
     * as templates for a new record.
     *
     * A limit of zero or less returns all favorites and no recent records.
     *
     * @return array<FavoriteTimesheet>
     */
    public function favoriteEntries(User $user, int $limit = 5): array
    {
        /** @var array<int> $favIds */
        $favIds = $this->getBookmark($user)->getContent();
        $recentIds = [];
        if (\count($favIds) < $limit) {
            $recentIds = $this->repository->getRecentActivityIds($user, null, $limit);
        }
        /** @var array<int> $ids */
        $ids = array_unique(array_merge($favIds, $recentIds));

        /** @var array<int, bool|FavoriteTimesheet> $favorites */
        $favorites = [];
        foreach ($ids as $id) {
            $favorites[$id] = \in_array($id, $favIds, true);
        }

        $all = [];
        if (\count($ids) > 0) {
            $timesheets = $this->repository->findTimesheetsById($user, $ids);
            foreach ($timesheets as $timesheet) {
                $id = $timesheet->getId();
                if ($id === null) {
                    continue;
                }
                $favorites[$id] = new FavoriteTimesheet($timesheet, $favorites[$id]);
            }

            foreach ($favorites as $id => $favorite) {
                if (!$favorite instanceof FavoriteTimesheet) {
                    // auto cleanup in case someone deleted a bookmarked timesheet
                    $this->removeFavoriteById($user, $id);
                    continue;
                }

                $all[$id] = $favorite;
            }
        }

        $all = array_values($all);

        if ($limit <= 0) {
            return $all;
        }

        return \array_slice($all, 0, $limit);
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

        if (\count($ids) >= self::MAX_FAVORITES) {
            array_pop($ids); // remove the last element and make space for a new id
        }
        array_unshift($ids, $timesheet->getId());
        $bookmark->setContent($ids);

        $this->bookmarkRepository->saveBookmark($bookmark);
    }

    public function removeFavorite(Timesheet $timesheet): void
    {
        if ($timesheet->getUser() === null) {
            throw new \InvalidArgumentException('Cannot remove favorite without user');
        }

        if ($timesheet->getId() === null) {
            throw new \InvalidArgumentException('Cannot remove unsaved favorite');
        }

        $this->removeFavoriteById($timesheet->getUser(), $timesheet->getId());
    }

    public function removeFavoriteById(User $user, int $timesheetId): void
    {
        $bookmark = $this->getBookmark($user);
        $ids = $bookmark->getContent();

        if (!\in_array($timesheetId, $ids)) {
            return;
        }

        $newIds = [];
        foreach ($ids as $id) {
            if ($id !== $timesheetId) {
                $newIds[] = $id;
            }
        }
        $bookmark->setContent($newIds);
        $this->bookmarkRepository->saveBookmark($bookmark);
    }
}
