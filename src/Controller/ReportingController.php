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
     *
     * @return Response
     */
    public function defaultReport(ReportingService $reportingService): Response
    {
        $user = $this->getUser();

        $defaultReport = $user->getPreferenceValue('reporting.initial_view', ReportingService::DEFAULT_VIEW);
        $allReports = $reportingService->getAvailableReports($user);

        return $this->render('reporting/index.html.twig', [
            'reports' => $allReports,
            'default' => $defaultReport,
        ]);
    }
}
