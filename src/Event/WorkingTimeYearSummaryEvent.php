<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\WorkingTime\Model\Year;
use App\WorkingTime\Model\YearPerUserSummary;
use App\WorkingTime\Model\YearSummary;
use Symfony\Contracts\EventDispatcher\Event;

final class WorkingTimeYearSummaryEvent extends Event
{
    public function __construct(private YearPerUserSummary $yearPerUserSummary, private \DateTimeInterface $until)
    {
    }

    public function getYear(): Year
    {
        return $this->yearPerUserSummary->getYear();
    }

    public function getUntil(): \DateTimeInterface
    {
        return $this->until;
    }

    public function addSummary(YearSummary $yearSummary): void
    {
        $this->yearPerUserSummary->addSummary($yearSummary);
    }
}
