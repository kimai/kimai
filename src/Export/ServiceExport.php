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
     * @var ExportRenderer[]
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

    /**
     * @param ExportRenderer|ExportRendererInterface $renderer
     * @return ServiceExport
     */
    public function addRenderer($renderer): ServiceExport
    {
        if ($renderer instanceof ExportRenderer) {
            $this->renderer[] = $renderer;
        } elseif ($renderer instanceof ExportRendererInterface) {
            $this->renderer[] = new DeprecatedExportRenderer($renderer);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Export renderer "%s" must be an instanceof ExportRendererInterface or ExportRenderer', \get_class($renderer))
            );
        }

        return $this;
    }

    /**
     * @return ExportRenderer[]
     * @internal
     */
    public function getRenderer(): array
    {
        return $this->renderer;
    }

    /**
     * @param string $id
     * @return ExportRenderer|null
     * @internal
     */
    public function getRendererById(string $id): ?ExportRenderer
    {
        foreach ($this->renderer as $renderer) {
            if ($renderer->getId() === $id) {
                return $renderer;
            }
        }

        return null;
    }

    /**
     * @param TimesheetExportInterface|TimesheetExportRenderer $exporter
     * @return ServiceExport
     */
    public function addTimesheetExporter($exporter): ServiceExport
    {
        if ($exporter instanceof TimesheetExportRenderer) {
            $this->exporter[] = $exporter;
        } elseif ($exporter instanceof TimesheetExportInterface) {
            $this->exporter[] = new DeprecatedTimesheetExportRenderer($exporter);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Timesheet renderer "%s" must be an instanceof TimesheetExportRenderer or TimesheetExportInterface', \get_class($exporter))
            );
        }

        return $this;
    }

    /**
     * @return TimesheetExportRenderer[]
     * @internal
     */
    public function getTimesheetExporter(): array
    {
        return $this->exporter;
    }

    /**
     * @param string $id
     * @return TimesheetExportInterface|null
     * @internal
     */
    public function getTimesheetExporterById(string $id): ?TimesheetExportRenderer
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
