<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Entity\Timesheet;
use App\Export\RendererInterface;
use App\Repository\Query\TimesheetQuery;
use App\Timesheet\UserDateTimeFactory;
use App\Utils\HtmlToPdfConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PDFRenderer implements RendererInterface
{
    use RendererTrait;

    /**
     * @var \Twig_Environment
     */
    protected $twig;
    /**
     * @var UserDateTimeFactory
     */
    protected $dateTime;
    /**
     * @var HtmlToPdfConverter
     */
    protected $converter;

    /**
     * @param \Twig_Environment $twig
     * @param UserDateTimeFactory $dateTime
     */
    public function __construct(\Twig_Environment $twig, UserDateTimeFactory $dateTime, HtmlToPdfConverter $converter)
    {
        $this->twig = $twig;
        $this->dateTime = $dateTime;
        $this->converter = $converter;
    }

    /**
     * @param Timesheet[] $timesheets
     * @param TimesheetQuery $query
     * @return Response
     * @throws \Mpdf\MpdfException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function render(array $timesheets, TimesheetQuery $query): Response
    {
        $content = $this->twig->render('export/renderer/pdf.html.twig', [
            'entries' => $timesheets,
            'query' => $query,
            'now' => $this->dateTime->createDateTime(),
            'summaries' => $this->calculateSummary($timesheets),
        ]);

        $content = $this->converter->convertToPdf($content);

        $response = new Response($content);

        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'kimai-export.pdf');

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'pdf';
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return 'pdf';
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return 'pdf';
    }
}
