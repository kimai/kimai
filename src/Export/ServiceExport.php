<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Repository\Query\ExportQuery;

final class ServiceExport
{
    /**
     * @var ExportRendererInterface[]
     */
    private $renderer = [];
    /**
     * @var TimesheetExportInterface[]
     */
    private $exporter = [];
    /**
     * @var ExportRepositoryInterface[]
     */
    private $repositories = [];

    public function addRenderer(ExportRendererInterface $renderer): ServiceExport
    {
        $this->renderer[] = $renderer;

        return $this;
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

    public function addTimesheetExporter(TimesheetExportInterface $exporter): ServiceExport
    {
        $this->exporter[] = $exporter;

        return $this;
    }

    /**
     * @return TimesheetExportInterface[]
     */
    public function getTimesheetExporter(): array
    {
        return $this->exporter;
    }

    public function getTimesheetExporterById(string $id): ?TimesheetExportInterface
    {
        foreach ($this->exporter as $exporter) {
            if ($exporter->getId() === $id) {
                return $exporter;
            }
        }

        return null;
    }

    public function addExportRepository(ExportRepositoryInterface $repository): ServiceExport
    {
        $this->repositories[] = $repository;

        return $this;
    }

    public function getExportItems(ExportQuery $query)
    {
        $items = [];

        foreach ($this->repositories as $repository) {
            $items = array_merge($items, $repository->getExportItemsForQuery($query));
        }

        return $items;
    }

    public function setExported(array $items): void
    {
        foreach ($this->repositories as $repository) {
            $repository->setExported($items);
        }
    }
}
