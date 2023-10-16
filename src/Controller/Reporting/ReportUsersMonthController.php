<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Controller\AbstractController;
use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use App\Model\DailyStatistic;
use App\Reporting\MonthlyUserList\MonthlyUserList;
use App\Reporting\MonthlyUserList\MonthlyUserListForm;
use App\Repository\Query\UserQuery;
use App\Repository\UserRepository;
use App\Timesheet\TimesheetStatisticService;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/reporting/users')]
#[IsGranted('report:other')]
final class ReportUsersMonthController extends AbstractController
{
    #[Route(path: '/month', name: 'report_monthly_users', methods: ['GET', 'POST'])]
    public function report(Request $request, TimesheetStatisticService $statisticService, UserRepository $userRepository): Response
    {
        return $this->render(
            'reporting/report_user_list.html.twig',
            $this->getData($request, $statisticService, $userRepository)
        );
    }

    #[Route(path: '/month_export', name: 'report_monthly_users_export', methods: ['GET', 'POST'])]
    public function export(Request $request, TimesheetStatisticService $statisticService, UserRepository $userRepository): Response
    {
        $data = $this->getData($request, $statisticService, $userRepository);

        $content = $this->renderView('reporting/report_user_list_export.html.twig', $data);

        $reader = new Html();
        $spreadsheet = $reader->loadFromString($content);

        $writer = new BinaryFileResponseWriter(new XlsxWriter(), 'kimai-export-users-monthly');

        return $writer->getFileResponse($spreadsheet);
    }

    private function getData(Request $request, TimesheetStatisticService $statisticService, UserRepository $userRepository): array
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory();

        $values = new MonthlyUserList();
        $values->setDate($dateTimeFactory->getStartOfMonth());

        $form = $this->createFormForGetRequest(MonthlyUserListForm::class, $values, [
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
        ]);

        $form->submit($request->query->all(), false);

        $query = new UserQuery();
        $query->setSystemAccount(false);
        $query->setCurrentUser($currentUser);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $values->setDate($dateTimeFactory->getStartOfMonth());
            } else {
                if ($values->getTeam() !== null) {
                    $query->setSearchTeams([$values->getTeam()]);
                }
            }
        }

        $allUsers = $userRepository->getUsersForQuery($query);

        if ($values->getDate() === null) {
            $values->setDate($dateTimeFactory->getStartOfMonth());
        }

        /** @var \DateTime $start */
        $start = $values->getDate();
        $start->modify('first day of 00:00:00');

        $end = clone $start;
        $end->modify('last day of 23:59:59');

        $previous = clone $start;
        $previous->modify('-1 month');

        $next = clone $start;
        $next->modify('+1 month');

        $dayStats = [];
        $hasData = true;

        if (!empty($allUsers)) {
            $dayStats = $statisticService->getDailyStatistics($start, $end, $allUsers);
        }

        if (empty($dayStats)) {
            $dayStats = [new DailyStatistic($start, $end, $currentUser)];
            $hasData = false;
        }

        return [
            'period_attribute' => 'days',
            'dataType' => $values->getSumType(),
            'report_title' => 'report_monthly_users',
            'box_id' => 'monthly-user-list-reporting-box',
            'export_route' => 'report_monthly_users_export',
            'form' => $form->createView(),
            'current' => $start,
            'next' => $next,
            'previous' => $previous,
            'decimal' => $values->isDecimal(),
            'subReportDate' => $values->getDate(),
            'subReportRoute' => 'report_user_month',
            'stats' => $dayStats,
            'hasData' => $hasData,
        ];
    }
}
