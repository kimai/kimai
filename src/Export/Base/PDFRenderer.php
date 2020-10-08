<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Base;

use App\Export\ExportItemInterface;
use App\Repository\ProjectRepository;
use App\Repository\Query\TimesheetQuery;
use App\Timesheet\UserDateTimeFactory;
use App\Utils\HtmlToPdfConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Twig\Environment;

class PDFRenderer
{
    use RendererTrait;

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
    /**
     * @var string
     */
    private $id = 'pdf';
    /**
     * @var string
     */
    private $template = 'default.pdf.twig';

    public function __construct(Environment $twig, UserDateTimeFactory $dateTime, HtmlToPdfConverter $converter, ProjectRepository $projectRepository)
    {
        $this->twig = $twig;
        $this->dateTime = $dateTime;
        $this->converter = $converter;
        $this->projectRepository = $projectRepository;
    }

    protected function getTemplate(): string
    {
        return '@export/' . $this->template;
    }

    protected function getOptions(TimesheetQuery $query): array
    {
        $decimal = false;
        if (null !== $query->getCurrentUser()) {
            $decimal = (bool) $query->getCurrentUser()->getPreferenceValue('timesheet.export_decimal', $decimal);
        } elseif (null !== $query->getUser()) {
            $decimal = (bool) $query->getUser()->getPreferenceValue('timesheet.export_decimal', $decimal);
        }

        return ['decimal' => $decimal];
    }

    /**
     * @param ExportItemInterface[] $timesheets
     * @param TimesheetQuery $query
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render(array $timesheets, TimesheetQuery $query): Response
    {
        $summary = $this->calculateSummary($timesheets);
        $content = $this->twig->render($this->getTemplate(), array_merge([
            'entries' => $timesheets,
            'query' => $query,
            'now' => $this->dateTime->createDateTime(),
            'summaries' => $summary,
            'budgets' => $this->calculateProjectBudget($timesheets, $query, $this->projectRepository),
            'decimal' => false,
        ], $this->getOptions($query)));

        $content = $this->converter->convertToPdf($content);

        $response = new Response($content);

        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'kimai-export.pdf');

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    public function setTemplate(string $filename): PDFRenderer
    {
        $this->template = $filename;

        return $this;
    }

    public function setId(string $id): PDFRenderer
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
