<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Reporting\ReportingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to render reports.
 *
 * @Route(path="/reporting")
 * @Security("is_granted('view_reporting')")
 */
final class ReportingController extends AbstractController
{
    /**
     * @Route(path="/", name="reporting", methods={"GET"})
     */
    public function defaultReport(ReportingService $reportingService): Response
    {
        return $this->render('reporting/index.html.twig', [
            'reports' => $reportingService->getAvailableReports($this->getUser()),
        ]);
    }
}
