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
use App\Repository\Query\TimesheetQueryHint;
use App\Repository\TimesheetRepository;

final class TimesheetExportRepository implements ExportRepositoryInterface
{
    public function __construct(private readonly TimesheetRepository $repository)
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
        $query->addQueryHint(TimesheetQueryHint::CUSTOMER_META_FIELDS);
        $query->addQueryHint(TimesheetQueryHint::PROJECT_META_FIELDS);
        $query->addQueryHint(TimesheetQueryHint::ACTIVITY_META_FIELDS);
        $query->addQueryHint(TimesheetQueryHint::USER_PREFERENCES);

        return $this->repository->getTimesheetResult($query)->getResults();
    }

    public function getType(): string
    {
        return 'timesheet';
    }
}
