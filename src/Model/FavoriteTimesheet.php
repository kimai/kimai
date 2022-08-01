<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\Timesheet;

final class FavoriteTimesheet
{
    public function __construct(private Timesheet $timesheet, private bool $isFavorite)
    {
    }

    public function getTimesheet(): Timesheet
    {
        return $this->timesheet;
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }
}
