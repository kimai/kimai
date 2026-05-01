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
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Extension\SandboxExtension;

final class PDFRenderer implements DispositionInlineInterface, ExportRendererInterface
{
    use RendererTrait;
    use PDFRendererTrait;

    private array $pdfOptions = [];

    public function __construct(
        private readonly Environment $twig,
        private readonly HtmlToPdfConverter $converter,
        private readonly ProjectStatisticService $projectStatisticService,
        private string $id,
        private string $title,
        private string $template,
    )
    {
    }

    public function isInternal(): false
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

        $content = $this->twig->render($this->getTemplate(), [
            'entries' => $exportItems,
            'query' => $query,
            'summaries' => $summary,
            'budgets' => $this->calculateProjectBudget($exportItems, $query, $this->projectStatisticService),
            'decimal' => false,
            'pdfContext' => $context
        ]);

        $sandbox->disableSandbox();

        $pdfOptions = array_merge($context->getOptions(), $this->getPdfOptions());

        $content = $this->converter->convertToPdf($content, $pdfOptions);

        return $this->createPdfResponse($content, $context);
    }

    public function getId(): string
    {
        return $this->id;
    }
}
