<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Pdf\HtmlToPdfConverter;
use App\Project\ProjectStatisticService;
use Twig\Environment;

final class PdfRendererFactory
{
    public function __construct(
        private Environment $twig,
        private HtmlToPdfConverter $converter,
        private ProjectStatisticService $projectStatisticService
    ) {
    }

    public function create(string $id, string $template): PDFRenderer
    {
        $renderer = new PDFRenderer($this->twig, $this->converter, $this->projectStatisticService);
        $renderer->setId($id);
        $renderer->setTemplate($template);

        return $renderer;
    }
}
