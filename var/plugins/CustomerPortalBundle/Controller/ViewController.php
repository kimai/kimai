<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\Controller;

use App\Controller\AbstractController;
use App\Customer\CustomerStatisticService;
use App\Entity\Customer;
use App\Entity\Project;
use App\Project\ProjectStatisticService;
use KimaiPlugin\CustomerPortalBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\CustomerPortalBundle\Repository\SharedProjectTimesheetRepository;
use KimaiPlugin\CustomerPortalBundle\Service\ViewService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ViewController extends AbstractController
{
    public function __construct(
        private readonly ProjectStatisticService $projectStatisticService,
        private readonly CustomerStatisticService $customerStatisticsService,
        private readonly ViewService $viewService,
        private readonly SharedProjectTimesheetRepository $sharedProjectTimesheetRepository
    ) {
    }

    #[Route(path: '/auth/shared-project-timesheets/{project}/{shareKey}', methods: ['GET', 'POST'])]
    #[Route(path: '/auth/customer-portal/{shareKey}', name: 'customer_portal_view', methods: ['GET', 'POST'])]
    public function indexAction(#[MapEntity(mapping: ['shareKey' => 'shareKey'])] SharedProjectTimesheet $sharedPortal, Request $request): Response
    {
        $givenPassword = $request->get('spt-password');

        // Check access.
        if (!$this->viewService->hasAccess($sharedPortal, $givenPassword, $request)) {
            return $this->render('@CustomerPortal/view/auth.html.twig', [
                'invalidPassword' => $request->isMethod('POST') && $givenPassword !== null,
            ]);
        }

        if ($sharedPortal->getCustomer() !== null) {
            return $this->renderCustomerView($sharedPortal, $request);
        }

        if ($sharedPortal->getProject() !== null) {
            return $this->renderProjectView($sharedPortal, $sharedPortal->getProject(), $request);
        }

        throw $this->createNotFoundException('Invalid shared portal: neither customer nor project is set');
    }

    #[Route(path: '/auth/shared-project-timesheets/customer/{customer}/{shareKey}/project/{project}', methods: ['GET', 'POST'])]
    #[Route(path: '/auth/customer-portal/{shareKey}/p/{project}', name: 'customer_portal_project', methods: ['GET', 'POST'])]
    public function viewCustomerProjectAction(#[MapEntity(mapping: ['shareKey' => 'shareKey'])] SharedProjectTimesheet $sharedPortal, Project $project, Request $request): Response
    {
        $givenPassword = $request->get('spt-password');

        if ($project->getCustomer() !== $sharedPortal->getCustomer()) {
            throw $this->createAccessDeniedException('Requested project does not match customer');
        }

        if (!$this->viewService->hasAccess($sharedPortal, $givenPassword, $request)) {
            return $this->render('@CustomerPortal/view/auth.html.twig', [
                'invalidPassword' => $request->isMethod('POST') && $givenPassword !== null,
            ]);
        }

        return $this->renderProjectView($sharedPortal, $project, $request);
    }

    /**
     * @deprecated only here for backwards compatibility
     */
    #[Route(path: '/auth/shared-project-timesheets/customer/{customer}/{shareKey}', methods: ['GET', 'POST'])]
    public function viewCustomerAction(#[MapEntity(mapping: ['shareKey' => 'shareKey'])] SharedProjectTimesheet $sharedPortal): Response
    {
        return $this->redirectToRoute('customer_portal_view', ['shareKey' => $sharedPortal->getShareKey()]);
    }

    private function renderCustomerView(SharedProjectTimesheet $sharedPortal, Request $request): Response
    {
        $customer = $sharedPortal->getCustomer();
        if ($customer === null) {
            throw $this->createNotFoundException('Invalid portal: Customer not found');
        }

        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('m'));
        $detailsMode = $request->get('details', 'table');

        // Get time records.
        $timeRecords = $this->viewService->getTimeRecords($sharedPortal, $year, $month);

        // Calculate summary.
        $rateSum = 0;
        $durationSum = 0;
        foreach($timeRecords as $record) {
            $rateSum += $record->getRate();
            $durationSum += $record->getDuration();
        }

        // Prepare stats for charts.
        $annualChartVisible = $sharedPortal->isAnnualChartVisible();
        $monthlyChartVisible = $sharedPortal->isMonthlyChartVisible();

        $statsPerMonth = $annualChartVisible ? $this->viewService->getAnnualStats($sharedPortal, $year) : null;
        $statsPerDay = ($monthlyChartVisible && $detailsMode === 'chart')
            ? $this->viewService->getMonthlyStats($sharedPortal, $year, $month) : null;

        // we cannot call $this->getDateTimeFactory() as it throws a AccessDeniedException for anonymous users
        $timezone = $customer->getTimezone() ?? date_default_timezone_get();
        $date = new \DateTimeImmutable('now', new \DateTimeZone($timezone));
        $stats = $this->customerStatisticsService->getBudgetStatisticModel($customer, $date);
        $projects = $this->sharedProjectTimesheetRepository->getProjects($sharedPortal);
        $projectStats = $this->projectStatisticService->getBudgetStatisticModelForProjects($projects, $date);

        return $this->render('@CustomerPortal/view/customer.html.twig', [
            'portal' => $sharedPortal,
            'customer' => $customer,
            'timeRecords' => $timeRecords,
            'rateSum' => $rateSum,
            'durationSum' => $durationSum,
            'year' => $year,
            'month' => $month,
            'currency' => $customer->getCurrency(),
            'statsPerMonth' => $statsPerMonth,
            'monthlyChartVisible' => $monthlyChartVisible,
            'statsPerDay' => $statsPerDay,
            'detailsMode' => $detailsMode,
            'stats' => $stats,
            'projectStats' => $projectStats,
        ]);
    }

    private function renderProjectView(SharedProjectTimesheet $sharedProject, Project $project, Request $request): Response
    {
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', date('m'));
        $detailsMode = $request->get('details', 'table');
        $timeRecords = $this->viewService->getTimeRecords($sharedProject, $year, $month, $project);

        // Calculate summary.
        $rateSum = 0;
        $durationSum = 0;
        foreach($timeRecords as $record) {
            $rateSum += $record->getRate();
            $durationSum += $record->getDuration();
        }

        // Define currency.
        $currency = $project->getCustomer()?->getCurrency() ?? Customer::DEFAULT_CURRENCY;

        // Prepare stats for charts.
        $annualChartVisible = $sharedProject->isAnnualChartVisible();
        $monthlyChartVisible = $sharedProject->isMonthlyChartVisible();
        $statsPerMonth = $annualChartVisible ? $this->viewService->getAnnualStats($sharedProject, $year, $project) : null;
        $statsPerDay = ($monthlyChartVisible && $detailsMode === 'chart')
            ? $this->viewService->getMonthlyStats($sharedProject, $year, $month, $project) : null;

        // we cannot call $this->getDateTimeFactory() as it throws a AccessDeniedException for anonymous users
        $timezone = $project->getCustomer()?->getTimezone() ?? date_default_timezone_get();
        $date = new \DateTimeImmutable('now', new \DateTimeZone($timezone));

        $stats = $this->projectStatisticService->getBudgetStatisticModel($project, $date);

        return $this->render('@CustomerPortal/view/project.html.twig', [
            'portal' => $sharedProject,
            'timeRecords' => $timeRecords,
            'rateSum' => $rateSum,
            'durationSum' => $durationSum,
            'year' => $year,
            'month' => $month,
            'currency' => $currency,
            'statsPerMonth' => $statsPerMonth,
            'monthlyChartVisible' => $monthlyChartVisible,
            'statsPerDay' => $statsPerDay,
            'detailsMode' => $detailsMode,
            'stats' => $stats,
            'project' => $project,
        ]);
    }
}
