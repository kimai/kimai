<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Reporting\ReportingService;
use App\Utils\PageSetup;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used to render reports.
 */
#[Route(path: '/reporting')]
#[IsGranted('view_reporting')]
final class ReportingController extends AbstractController
{
    #[Route(path: '/', name: 'reporting', methods: ['GET'])]
    public function defaultReport(ReportingService $reportingService): Response
    {
        $page = new PageSetup('menu.reporting');
        $page->setHelp('reporting.html');

        return $this->render('reporting/index.html.twig', [
            'page_setup' => $page,
            'reports' => $reportingService->getAvailableReports($this->getUser()),
        ]);
    }
}
