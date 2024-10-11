<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\User;

/**
 * @internal
 */
class QuickEntryWeek
{
    /**
     * @var array<QuickEntryModel>
     */
    private array $rows = [];

    public function __construct(private \DateTime $startDate)
    {
    }

    public function addRow(User $user, ?Project $project = null, ?Activity $activity = null): QuickEntryModel
    {
        $model = $this->createRow($user, $project, $activity);

        $this->rows[] = $model;

        return $model;
    }

    public function createRow(User $user, ?Project $project = null, ?Activity $activity = null): QuickEntryModel
    {
        return new QuickEntryModel($user, $project, $activity);
    }

    public function getDate(): \DateTime
    {
        return $this->startDate;
    }

    public function countRows(): int
    {
        return \count($this->rows);
    }

    /**
     * @return QuickEntryModel[]
     */
    public function getRows(): array
    {
        $rows = $this->rows;

        // sort rows by projects - make it configurable in the future
        uasort($rows, [$this, 'sortByProjectName']);

        return $rows;
    }

    private function sortByProjectName(QuickEntryModel $a, QuickEntryModel $b): int
    {
        $aName = $a->getProject()?->getName();
        $bName = $b->getProject()?->getName();

        if ($aName === null && $bName === null) {
            $result = 0;
        } elseif ($aName === null && $bName !== null) {
            $result = 1;
        } elseif ($aName !== null && $bName === null) {
            $result = -1;
        } else {
            $result = strcmp((string) $aName, (string) $bName);
        }

        return  $result < 0 ? -1 : 1;
    }

    /**
     * @param QuickEntryModel[] $rows
     */
    public function setRows(array $rows): void
    {
        $this->rows = $rows;
    }
}
