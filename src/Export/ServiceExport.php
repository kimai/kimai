<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\ExportableItem;
use App\Entity\ExportTemplate;
use App\Event\ExportItemsQueryEvent;
use App\Export\Renderer\CsvRendererFactory;
use App\Export\Renderer\HtmlRendererFactory;
use App\Export\Renderer\PdfRendererFactory;
use App\Export\Renderer\XlsxRendererFactory;
use App\Repository\ExportTemplateRepository;
use App\Repository\Query\ExportQuery;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

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
     * @var ExportRendererInterface[]
     */
    private array $timesheetExporter = [];
    /**
     * @var ExportRepositoryInterface[]
     */
    private array $repositories = [];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly HtmlRendererFactory $htmlRendererFactory,
        private readonly PdfRendererFactory $pdfRendererFactory,
        private readonly CsvRendererFactory $csvRendererFactory,
        private readonly XlsxRendererFactory $xlsxRendererFactory,
        private readonly ExportTemplateRepository $exportTemplateRepository,
        private readonly LoggerInterface $logger,
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

    private function filenameToTitle(string $title): string
    {
        if (str_contains($title, '.')) {
            $title = explode('.', $title)[0];
        }

        return str_replace(['-', '_'], ' ', $title);
    }

    /**
     * @return ExportRendererInterface[]
     */
    public function getRenderer(): array
    {
        $renderer = [
            $this->csvRendererFactory->createDefault(),
            $this->xlsxRendererFactory->createDefault(),
            $this->pdfRendererFactory->create('pdf', 'export/pdf-layout.html.twig', 'pdf'),
            $this->htmlRendererFactory->create('print', 'export/print.html.twig'),
        ];

        foreach ($this->exportTemplateRepository->findAll() as $template) {
            try {
                $renderer[] = $this->createTemplateFromExportTemplate($template);
            } catch (\Exception $exception) {
                $this->logger->error('Unknown export template type: ' . $template->getRenderer());
            }
        }

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

                    $renderer[] = $this->htmlRendererFactory->create($tplName, '@export/' . $tplName, $this->filenameToTitle($tplName));
                }
            }

            $pdfTemplates = glob($exportPath . '/*.pdf.twig');
            if (\is_array($pdfTemplates)) {
                foreach ($pdfTemplates as $pdfTpl) {
                    $tplName = basename($pdfTpl);
                    if (stripos($tplName, '-bundle') !== false) {
                        continue;
                    }

                    $renderer[] = $this->pdfRendererFactory->create($tplName, '@export/' . $tplName, $this->filenameToTitle($tplName));
                }
            }
        }

        return array_merge($this->renderer, $renderer);
    }

    private function createTemplateFromExportTemplate(ExportTemplate $template): ExportRendererInterface
    {
        $tpl = new Template((string) $template->getId(), $template->getTitle()); // @phpstan-ignore argument.type
        $tpl->setColumns($template->getColumns());
        $tpl->setLocale($template->getLanguage());
        $tpl->setOptions($template->getOptions());

        switch ($template->getRenderer()) {
            case 'csv':
                return $this->csvRendererFactory->create($tpl);

            case 'xlsx':
                return $this->xlsxRendererFactory->create($tpl);

            case 'pdf':
                return $this->pdfRendererFactory->createFromTemplate($tpl);

            default:
                throw new \Exception('Unknown export template type: ' . $template->getRenderer());
        }
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

    public function addTimesheetExporter(ExportRendererInterface $exporter): void
    {
        $this->timesheetExporter[] = $exporter;
    }

    /**
     * @return ExportRendererInterface[]
     */
    public function getTimesheetExporter(): array
    {
        // TODO 3.0 cache the result, as this is one extra database query on the timesheet pages
        $exporter = [
            $this->pdfRendererFactory->create('pdf', '@export/timesheet.pdf.twig'),
            $this->xlsxRendererFactory->createDefault(),
            $this->csvRendererFactory->createDefault(),
            $this->htmlRendererFactory->create('print', 'timesheet/export.html.twig'),
        ];

        foreach ($this->exportTemplateRepository->findAll() as $template) {
            if (!$template->isAvailableForAll()) {
                continue;
            }
            try {
                $exporter[] = $this->createTemplateFromExportTemplate($template);
            } catch (\Exception $exception) {
                $this->logger->error('Unknown export template type: ' . $template->getRenderer());
            }
        }

        return array_merge($this->timesheetExporter, $exporter);
    }

    public function getTimesheetExporterById(string $id): ?ExportRendererInterface
    {
        foreach ($this->getTimesheetExporter() as $exporter) {
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
