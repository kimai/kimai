<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\ExportableItem;
use App\Event\ExportItemsQueryEvent;
use App\Repository\Query\ExportQuery;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ServiceExport
{
    /**
     * @var ExportRendererInterface[]
     */
    private $renderer = [];
    /**
     * @var TimesheetExportInterface[]
     */
    private $timesheetExporter = [];
    /**
     * @var ExportRepositoryInterface[]
     */
    private $repositories = [];

    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function addRenderer(ExportRendererInterface $renderer): void
    {
        $this->renderer[] = $renderer;
    }

    /**
     * @return ExportRendererInterface[]
     */
    public function getRenderer(): array
    {
        return $this->renderer;
    }

    public function getRendererById(string $id): ?ExportRendererInterface
    {
        foreach ($this->renderer as $renderer) {
            if ($renderer->getId() === $id) {
                return $renderer;
            }
        }

        return null;
    }

    public function addTimesheetExporter(TimesheetExportInterface $exporter): void
    {
        $this->timesheetExporter[] = $exporter;
    }

    /**
     * @return TimesheetExportInterface[]
     */
    public function getTimesheetExporter(): array
    {
        return $this->timesheetExporter;
    }

    public function getTimesheetExporterById(string $id): ?TimesheetExportInterface
    {
        foreach ($this->timesheetExporter as $exporter) {
            if ($exporter->getId() === $id) {
                return $exporter;
            }
        }

        return null;
    }

    public function addExportRepository(ExportRepositoryInterface $repository): void
    {
        $this->repositories[] = $repository;
    }

    /**
     * @param ExportQuery $query
     * @return ExportableItem[]
     * @throws TooManyItemsExportException
     */
    public function getExportItems(ExportQuery $query): array
    {
        $items = [];

        $max = $this->getMaximumResults($query);

        foreach ($this->repositories as $repository) {
            $items = array_merge($items, $repository->getExportItemsForQuery($query));
            if ($max !== null && \count($items) > $max) {
                throw new TooManyItemsExportException(
                    sprintf('Limit reached! Expected max. %s items but got %s', $max, \count($items))
                );
            }
        }

        return $items;
    }

    public function setExported(array $items): void
    {
        foreach ($this->repositories as $repository) {
            $repository->setExported($items);
        }
    }

    public function getMaximumResults(ExportQuery $query): ?int
    {
        $event = new ExportItemsQueryEvent($query);
        $this->eventDispatcher->dispatch($event);

        return $event->getExportQuery()->getMaxResults();
    }
}
