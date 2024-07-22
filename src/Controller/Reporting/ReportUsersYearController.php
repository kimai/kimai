<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Configuration\SystemConfiguration;
use App\Controller\AbstractController;
use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use App\Model\MonthlyStatistic;
use App\Reporting\YearlyUserList\YearlyUserList;
use App\Reporting\YearlyUserList\YearlyUserListForm;
use App\Repository\Query\TimesheetStatisticQuery;
use App\Repository\Query\UserQuery;
use App\Repository\Query\VisibilityInterface;
use App\Repository\UserRepository;
use App\Timesheet\TimesheetStatisticService;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/reporting/users')]
#[IsGranted('report:other')]
final class ReportUsersYearController extends AbstractController
{
    #[Route(path: '/year', name: 'report_yearly_users', methods: ['GET', 'POST'])]
    public function report(Request $request, SystemConfiguration $systemConfiguration, TimesheetStatisticService $statisticService, UserRepository $userRepository): Response
    {
        return $this->render(
            'reporting/report_user_list_monthly.html.twig',
            $this->getData($request, $systemConfiguration, $statisticService, $userRepository)
        );
    }

    #[Route(path: '/year_export', name: 'report_yearly_users_export', methods: ['GET', 'POST'])]
    public function export(Request $request, SystemConfiguration $systemConfiguration, TimesheetStatisticService $statisticService, UserRepository $userRepository): Response
    {
        $data = $this->getData($request, $systemConfiguration, $statisticService, $userRepository);

        $content = $this->renderView('reporting/report_user_list_monthly_export.html.twig', $data);

        $reader = new Html();
        $spreadsheet = $reader->loadFromString($content);

        $writer = new BinaryFileResponseWriter(new XlsxWriter(), 'kimai-export-users-yearly');

        return $writer->getFileResponse($spreadsheet);
    }

    private function getData(Request $request, SystemConfiguration $systemConfiguration, TimesheetStatisticService $statisticService, UserRepository $userRepository): array
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory();

        $defaultDate = $dateTimeFactory->createStartOfYear();
        $isFinancialYear = false;

        if (null !== ($financialYear = $systemConfiguration->getFinancialYearStart())) {
            $defaultDate = $this->getDateTimeFactory()->createStartOfFinancialYear($financialYear);
            $isFinancialYear = true;
        }

        $values = new YearlyUserList();
        $values->setDate(clone $defaultDate);

        $form = $this->createFormForGetRequest(YearlyUserListForm::class, $values, [
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
            'show_range' => $isFinancialYear,
        ]);

        $form->submit($request->query->all(), false);

        $query = new UserQuery();
        $query->setVisibility(VisibilityInterface::SHOW_BOTH);
        $query->setSystemAccount(false);
        $query->setCurrentUser($currentUser);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $values->setDate(clone $defaultDate);
            } else {
                if ($values->getTeam() !== null) {
                    $query->setSearchTeams([$values->getTeam()]);
                }
            }
        }

        $allUsers = $userRepository->getUsersForQuery($query);

        if ($values->getDate() === null) {
            $values->setDate(clone $defaultDate);
        }

        /** @var \DateTime $start */
        $start = $values->getDate();

        // there is a potential edge case bug for financial years:
        // the last month will be skipped, if the financial year started on a different day than the first
        $end = $dateTimeFactory->createEndOfFinancialYear($start);

        $monthStats = [];
        $hasData = true;

        if (!empty($allUsers)) {
            $statsQuery = new TimesheetStatisticQuery($start, $end, $allUsers);
            $statsQuery->setProject($values->getProject());
            $monthStats = $statisticService->getMonthlyStats($statsQuery);
        }

        if (empty($monthStats)) {
            $monthStats = [new MonthlyStatistic($start, $end, $currentUser)];
            $hasData = false;
        }

        return [
            'subReportDate' => $values->getDate(),
            'period_attribute' => 'months',
            'dataType' => $values->getSumType(),
            'report_title' => 'report_yearly_users',
            'box_id' => 'yearly-user-list-reporting-box',
            'export_route' => 'report_yearly_users_export',
            'decimal' => $values->isDecimal(),
            'form' => $form->createView(),
            'stats' => $monthStats,
            'hasData' => $hasData,
        ];
    }
}
