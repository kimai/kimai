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
use App\Export\Renderer\HtmlRendererFactory;
use App\Export\Renderer\PdfRendererFactory;
use App\Repository\Query\ExportQuery;
use Psr\EventDispatcher\EventDispatcherInterface;

final class ServiceExport
{
    /**
     * @var array<int, string>
     */
    private array $documentDirs = [];
    /**
     * @var ExportRendererInterface[]
     */
    private array $renderer = [];
    /**
     * @var TimesheetExportInterface[]
     */
    private array $timesheetExporter = [];
    /**
     * @var ExportRepositoryInterface[]
     */
    private array $repositories = [];

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private HtmlRendererFactory $htmlRendererFactory,
        private PdfRendererFactory $pdfRendererFactory
    )
    {
    }

    /**
     * @CloudRequired
     */
    public function addDirectory(string $directory): void
    {
        $this->documentDirs[] = $directory;
    }

    /**
     * @CloudRequired
     */
    public function removeDirectory(string $directory): void
    {
        if (($key = array_search($directory, $this->documentDirs, true)) !== false) {
            unset($this->documentDirs[$key]);
        }
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
        $renderer = [];

        foreach ($this->documentDirs as $exportPath) {
            if (!is_dir($exportPath)) {
                continue;
            }

            $htmlTemplates = glob($exportPath . '/*.html.twig');
            if (\is_array($htmlTemplates)) {
                foreach ($htmlTemplates as $htmlTpl) {
                    $tplName = basename($htmlTpl);
                    if (stripos($tplName, '-bundle') !== false) {
                        continue;
                    }

                    $renderer[] = $this->htmlRendererFactory->create($tplName, $tplName);
                }
            }

            $pdfTemplates = glob($exportPath . '/*.pdf.twig');
            if (\is_array($pdfTemplates)) {
                foreach ($pdfTemplates as $pdfTpl) {
                    $tplName = basename($pdfTpl);
                    if (stripos($tplName, '-bundle') !== false) {
                        continue;
                    }

                    $renderer[] = $this->pdfRendererFactory->create($tplName, $tplName);
                }
            }
        }

        return array_merge($this->renderer, $renderer);
    }

    public function getRendererById(string $id): ?ExportRendererInterface
    {
        foreach ($this->getRenderer() as $renderer) {
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
                    \sprintf('Limit reached! Expected max. %s items but got %s', $max, \count($items))
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
