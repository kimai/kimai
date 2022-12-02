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
        $route = null;

        $defaultReport = $user->getPreferenceValue('reporting.initial_view', ReportingService::DEFAULT_VIEW, false);
        $allReports = $reportingService->getAvailableReports($user);

        foreach ($allReports as $report) {
            if ($report->getId() === $defaultReport) {
                $route = $report->getRoute();
                break;
            }
        }

        // fallback, if the configured report could not be found
        // e.g. when it was deleted or replaced by an enhanced version with a new id
        if ($route === null && \count($allReports) > 0) {
            $report = $allReports[array_keys($allReports)[0]];
            $route = $report->getRoute();
        }

        if ($route === null) {
            throw $this->createNotFoundException('Unknown default report');
        }

        return $this->redirectToRoute($route);
    }
}
