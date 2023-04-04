<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\WorkingTime\Model\Year;
use App\WorkingTime\Model\YearSummary;
use Symfony\Contracts\EventDispatcher\Event;

final class WorkingTimeYearSummaryEvent extends Event
{
    /**
     * @var array<YearSummary>
     */
    private array $summaries = [];

    public function __construct(private Year $year)
    {
    }

    public function getYear(): Year
    {
        return $this->year;
    }

    public function addSummary(YearSummary $yearSummary): void
    {
        $this->summaries[] = $yearSummary;
    }

    /**
     * @return YearSummary[]
     */
    public function getSummaries(): array
    {
        return $this->summaries;
    }
}
