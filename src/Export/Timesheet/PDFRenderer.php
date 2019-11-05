<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Timesheet;

use App\Entity\Timesheet;
use App\Event\TimesheetMetaDisplayEvent;
use App\Export\TimesheetExportInterface;
use App\Repository\Query\TimesheetQuery;
use App\Timesheet\UserDateTimeFactory;
use App\Utils\HtmlToPdfConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Twig\Environment;

final class PDFRenderer implements TimesheetExportInterface
{
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var HtmlToPdfConverter
     */
    private $converter;
    /**
     * @var UserDateTimeFactory
     */
    private $dateTime;

    public function __construct(Environment $twig, HtmlToPdfConverter $converter, UserDateTimeFactory $dateTime)
    {
        $this->twig = $twig;
        $this->converter = $converter;
        $this->dateTime = $dateTime;
    }

    /**
     * @param Timesheet[] $timesheets
     * @param TimesheetQuery $query
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render(array $timesheets, TimesheetQuery $query): Response
    {
        $metaLocation = TimesheetMetaDisplayEvent::TEAM_TIMESHEET_EXPORT;
        if (null !== $query->getUser()) {
            $metaLocation = TimesheetMetaDisplayEvent::EXPORT;
        }

        $content = $this->twig->render('timesheet/export-pdf.html.twig', [
            'entries' => $timesheets,
            'query' => $query,
            'now' => $this->dateTime->createDateTime(),
        ]);

        $content = $this->converter->convertToPdf($content);

        $response = new Response($content);

        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'timesheet-export.pdf');

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
}
