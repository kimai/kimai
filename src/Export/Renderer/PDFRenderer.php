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
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
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
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
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
            'now' => new \DateTime(),
            'summaries' => $this->calculateSummary($timesheets),
        ]);

        //return new Response($content);

        $mpdf = new Mpdf();
        $mpdf->WriteHTML($content);
        $content = $mpdf->Output('test', Destination::STRING_RETURN);

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
