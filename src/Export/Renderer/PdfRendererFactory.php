<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Export\Base\PDFRenderer;
use App\Export\Base\PdfTemplateRenderer;
use App\Export\ColumnConverter;
use App\Export\Template;
use App\Pdf\HtmlToPdfConverter;
use App\Project\ProjectStatisticService;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\Environment;

final class PdfRendererFactory
{
    public function __construct(
        private readonly Environment $twig,
        private readonly HtmlToPdfConverter $converter,
        private readonly ProjectStatisticService $projectStatisticService,
        private readonly LocaleSwitcher $localeSwitcher,
        private readonly ColumnConverter $columnConverter
    ) {
    }

    public function create(string $id, string $template, ?string $title = null): PDFRenderer
    {
        return new PDFRenderer(
            $this->twig,
            $this->converter,
            $this->projectStatisticService,
            $id,
            $title ?? $id,
            $template,
        );
    }

    public function createFromTemplate(Template $template): PdfTemplateRenderer
    {
        return new PdfTemplateRenderer(
            $this->twig,
            $this->converter,
            $this->projectStatisticService,
            $this->columnConverter,
            $this->localeSwitcher,
            $template
        );
    }
}
