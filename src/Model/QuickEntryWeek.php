<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

/**
 * @internal
 */
class QuickEntryWeek
{
    private $date;
    private $rows;

    /**
     * @param \DateTime $startDate
     * @param QuickEntryModel[] $rows
     */
    public function __construct(\DateTime $startDate, array $rows)
    {
        $this->date = $startDate;
        $this->rows = $rows;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @return QuickEntryModel[]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @param QuickEntryModel[] $rows
     */
    public function setRows(array $rows): void
    {
        $this->rows = $rows;
    }
}
