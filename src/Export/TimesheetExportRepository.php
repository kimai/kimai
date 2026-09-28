<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\Timesheet;
use App\Event\TimesheetMetaDisplayEvent;
use App\Repository\Query\ExportQuery;
use App\Repository\Query\TimesheetQueryHint;
use App\Repository\TimesheetRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

final class TimesheetExportRepository implements ExportRepositoryInterface, ExportPreviewColumnProviderInterface
{
    public function __construct(
        private readonly TimesheetRepository $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
    )
    {
    }

    public function getExportPreviewColumns(ExportQuery $query): array
    {
        $event = new TimesheetMetaDisplayEvent($query, TimesheetMetaDisplayEvent::EXPORT);
        $this->eventDispatcher->dispatch($event);

        return $event->getFields();
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
