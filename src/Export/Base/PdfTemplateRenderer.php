<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Entity\ExportableItem;
use App\Export\ColumnConverter;
use App\Export\ExportFilename;
use App\Export\ExportRendererInterface;
use App\Export\TemplateInterface;
use App\Form\Type\PdfFontType;
use App\Pdf\HtmlToPdfConverter;
use App\Pdf\PdfContext;
use App\Pdf\PdfRendererTrait;
use App\Project\ProjectStatisticService;
use App\Repository\Query\TimesheetQuery;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\Environment;

#[Exclude]
final class PdfTemplateRenderer implements DispositionInlineInterface, ExportRendererInterface
{
    use RendererTrait;
    use PDFRendererTrait;

    public function __construct(
        private readonly Environment $twig,
        private readonly HtmlToPdfConverter $converter,
        private readonly ProjectStatisticService $projectStatisticService,
        private readonly ColumnConverter $columnConverter,
        private readonly LocaleSwitcher $localeSwitcher,
        private readonly TemplateInterface $template
    )
    {
    }

    public function isInternal(): bool
    {
        return true;
    }

    public function getType(): string
    {
        return 'pdf';
    }

    public function getTitle(): string
    {
        return $this->template->getTitle();
    }

    public function getId(): string
    {
        return $this->template->getId();
    }

    /**
     * @param ExportableItem[] $exportItems
     */
    public function render(array $exportItems, TimesheetQuery $query): Response
    {
        $filename = new ExportFilename($query);
        $context = new PdfContext();
        $context->setOption('filename', $filename->getFilename());
        $context->setOption('margin_top', 22);
        $context->setOption('margin_bottom', 22);
        $context->setOption('PDFA', true);
        $context->setOption('PDFAauto', true);

        $options = $this->template->getOptions();

        $summaryColumns = [];
        if (\array_key_exists('summary_columns', $options) && \is_string($options['summary_columns']) && $options['summary_columns'] !== '') {
            $summaryColumns = $options['summary_columns'];
            $summaryColumns = explode(',', $summaryColumns);
            unset($options['summary_columns']);
        }
        $options['summary'] = $summaryColumns;
        if (\count($summaryColumns) > 0) {
            $options['summaries'] = $this->calculateSummary($exportItems);
            $options['budgets'] = $this->calculateProjectBudget($exportItems, $query, $this->projectStatisticService);
        }

        $font = 'sans-serif';
        if (\array_key_exists('font', $options) && \in_array($options['font'], PdfFontType::AVAILABLE_FONTS, true)) {
            $font = $options['font'];
        }

        $format = 'A4';
        if (\array_key_exists('pageSize', $options) && \is_string($options['pageSize']) && \in_array($options['pageSize'], ['A4', 'A5', 'A6', 'Legal', 'Letter'])) {
            $format = $options['pageSize'];
        }
        if (\array_key_exists('orientation', $options) && $options['orientation'] === 'landscape') {
            $format .= '-L';
        }
        $context->setOption('format', $format);

        $oldLocale = null;
        $locale = $this->template->getLocale();
        if ($locale !== null) {
            $oldLocale = $this->localeSwitcher->getLocale();
            $this->localeSwitcher->setLocale($locale);
        }

        $content = $this->twig->render('export/renderer.pdf.twig', array_merge([
            'template' => $this->template,
            'title' => $options['name'] ?? null,
            'locale' => $locale,
            'entries' => $exportItems,
            'query' => $query,
            'font' => $font,
            'columns' => $this->columnConverter->getColumns($this->template, $query),
        ], $options));

        if ($oldLocale !== null) {
            $this->localeSwitcher->setLocale($locale);
        }

        $pdfOptions = array_merge($context->getOptions(), $options);

        $content = $this->converter->convertToPdf($content, $pdfOptions);

        return $this->createPdfResponse($content, $context);
    }
}
