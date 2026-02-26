<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Entity\ExportableItem;
use App\Export\ExportFilename;
use App\Export\ExportRendererInterface;
use App\Pdf\HtmlToPdfConverter;
use App\Pdf\PdfContext;
use App\Pdf\PdfRendererTrait;
use App\Project\ProjectStatisticService;
use App\Repository\Query\TimesheetQuery;
use App\Twig\SecurityPolicy\StrictPolicy;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Extension\SandboxExtension;

/**
 * TODO 3.0 remove default values from constructor parameters and make class final
 * @final
 */
#[Exclude]
class PDFRenderer implements DispositionInlineInterface, ExportRendererInterface
{
    use RendererTrait;
    use PDFRendererTrait;

    private array $pdfOptions = [];

    public function __construct(
        private readonly Environment $twig,
        private readonly HtmlToPdfConverter $converter,
        private readonly ProjectStatisticService $projectStatisticService,
        private string $id = 'pdf', // deprecated default parameter - TODO 3.0
        private string $title = 'pdf', // deprecated default parameter - TODO 3.0
        private string $template = 'export/pdf-layout.html.twig', // deprecated default parameter - TODO 3.0
    )
    {
    }

    public function isInternal(): bool
    {
        return false;
    }

    public function getType(): string
    {
        return 'pdf';
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    protected function getTemplate(): string
    {
        return $this->template;
    }

    protected function getOptions(TimesheetQuery $query): array
    {
        $decimal = false;
        if (null !== $query->getCurrentUser()) {
            $decimal = $query->getCurrentUser()->isExportDecimal();
        } elseif (null !== $query->getUser()) {
            $decimal = $query->getUser()->isExportDecimal();
        }

        return ['decimal' => $decimal];
    }

    public function getPdfOptions(): array
    {
        return $this->pdfOptions;
    }

    public function setPdfOption(string $key, string $value): void
    {
        $this->pdfOptions[$key] = $value;
    }

    /**
     * @param ExportableItem[] $exportItems
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render(array $exportItems, TimesheetQuery $query): Response
    {
        $filename = new ExportFilename($query);
        $context = new PdfContext();
        $context->setOption('filename', $filename->getFilename());

        $summary = $this->calculateSummary($exportItems);

        // enable basic security measures
        if (!$this->twig->hasExtension(SandboxExtension::class)) {
            $this->twig->addExtension(new SandboxExtension(new StrictPolicy()));
        }

        $sandbox = $this->twig->getExtension(SandboxExtension::class);
        $sandbox->enableSandbox();

        $content = $this->twig->render($this->getTemplate(), array_merge([
            'entries' => $exportItems,
            'query' => $query,
            'summaries' => $summary,
            'budgets' => $this->calculateProjectBudget($exportItems, $query, $this->projectStatisticService),
            'decimal' => false,
            'pdfContext' => $context
        ], $this->getOptions($query)));

        $sandbox->disableSandbox();

        $pdfOptions = array_merge($context->getOptions(), $this->getPdfOptions());

        $content = $this->converter->convertToPdf($content, $pdfOptions);

        return $this->createPdfResponse($content, $context);
    }

    /**
     * @deprecated since 2.40.0
     */
    public function setTemplate(string $filename): void
    {
        $this->template = '@export/' . $filename;
    }

    /**
     * @deprecated since 2.40.0
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @deprecated since 2.40.0
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
