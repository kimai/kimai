<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Repository\ProjectRepository;
use App\Timesheet\UserDateTimeFactory;
use App\Utils\HtmlToPdfConverter;
use Twig\Environment;

final class PdfRendererFactory
{
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var UserDateTimeFactory
     */
    private $dateTime;
    /**
     * @var HtmlToPdfConverter
     */
    private $converter;
    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    public function __construct(Environment $twig, UserDateTimeFactory $dateTime, HtmlToPdfConverter $converter, ProjectRepository $projectRepository)
    {
        $this->twig = $twig;
        $this->dateTime = $dateTime;
        $this->converter = $converter;
        $this->projectRepository = $projectRepository;
    }

    public function create(string $id, string $template): PDFRenderer
    {
        $renderer = new PDFRenderer($this->twig, $this->dateTime, $this->converter, $this->projectRepository);
        $renderer->setId($id);
        $renderer->setTemplate($template);

        return $renderer;
    }
}
