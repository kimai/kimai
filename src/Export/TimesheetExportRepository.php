<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\Timesheet;
use App\Repository\Query\ExportQuery;
use App\Repository\TimesheetRepository;

final class TimesheetExportRepository implements ExportRepositoryInterface
{
    public function __construct(private TimesheetRepository $repository)
    {
    }

    /**
     * @param Timesheet[] $items
     */
    public function setExported(array $items): void
    {
        $timesheets = [];

        foreach ($items as $item) {
            if ($item instanceof Timesheet) {
                $timesheets[] = $item;
            }
        }

        if (empty($timesheets)) {
            return;
        }

        $this->repository->setExported($timesheets);
    }

    public function getExportItemsForQuery(ExportQuery $query): iterable
    {
        return $this->repository->getTimesheetsForQuery($query, true);
    }

    public function getType(): string
    {
        return 'timesheet';
    }
}
