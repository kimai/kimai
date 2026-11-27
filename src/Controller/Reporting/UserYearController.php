<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Entity\User;
use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use App\Model\DateStatisticInterface;
use App\Model\MonthlyStatistic;
use App\Reporting\YearByUser\YearByUser;
use App\Reporting\YearByUser\YearByUserForm;
use App\Repository\Query\TimesheetStatisticQuery;
use DateTime;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/reporting/user')]
#[IsGranted('report:user')]
final class UserYearController extends AbstractUserReportController
{
    #[Route(path: '/year', name: 'report_user_year', methods: ['GET', 'POST'])]
    public function yearByUser(Request $request): Response
    {
        return $this->render('reporting/report_by_user_year.html.twig', $this->getData($request));
    }

    #[Route(path: '/year_export', name: 'report_user_year_export', methods: ['GET', 'POST'])]
    public function export(Request $request): Response
    {
        $data = $this->getData($request, true);

        $content = $this->renderView('reporting/report_by_user_year_export.html.twig', $data);

        $reader = new Html();
        $spreadsheet = $reader->loadFromString($content);

        $writer = new BinaryFileResponseWriter(new XlsxWriter(), 'kimai-export-user-yearly');

        return $writer->getFileResponse($spreadsheet);
    }

    private function getData(Request $request, bool $export = false): array
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory($currentUser);
        $canChangeUser = $this->canSelectUser();

        $values = new YearByUser();
        $values->setDecimal($export);
        $values->setUser($currentUser);

        $defaultDate = $dateTimeFactory->createStartOfYear();

        $values->setDate(clone $defaultDate);

        $form = $this->createFormForGetRequest(YearByUserForm::class, $values, [
            'include_user' => $canChangeUser,
            'timezone' => $dateTimeFactory->getTimezone()->getName(),
            'start_date' => $values->getDate(),
        ]);

        $form->submit($request->query->all(), false);

        if ($values->getUser() === null) {
            $values->setUser($currentUser);
        }

        if ($currentUser !== $values->getUser() && !$canChangeUser) {
            throw new AccessDeniedException('User is not allowed to see other users timesheet');
        }

        if ($values->getDate() === null) {
            $values->setDate(clone $defaultDate);
        }

        // normalize to the full calendar year, no matter which date was submitted
        $start = $dateTimeFactory->createStartOfYear($values->getDate());
        $end = $dateTimeFactory->createEndOfYear($start);

        $selectedUser = $values->getUser();

        $previous = DateTime::createFromInterface($start);
        $previous->modify('-1 year');

        $next = DateTime::createFromInterface($start);
        $next->modify('+1 year');

        $data = $this->prepareReport($start, $end, $selectedUser);

        return [
            'decimal' => $values->isDecimal(),
            'dataType' => $values->getSumType(),
            'report_title' => 'report_user_year',
            'box_id' => 'user-year-reporting-box',
            'form' => $form->createView(),
            'period' => new MonthlyStatistic($start, $end, $selectedUser),
            'rows' => $data,
            'user' => $selectedUser,
            'current' => $start,
            'next' => $next,
            'previous' => $previous,
            'begin' => $start,
            'end' => $end,
            'export_route' => 'report_user_year_export',
        ];
    }

    protected function getStatisticDataRaw(DateTimeInterface $begin, DateTimeInterface $end, User $user): array
    {
        $query = new TimesheetStatisticQuery($begin, $end, [$user]);
        $query->setCurrentUser($this->getUser());

        return $this->statisticService->getMonthlyStatisticsGrouped($query);
    }

    protected function createStatisticModel(DateTimeInterface $begin, DateTimeInterface $end, User $user): DateStatisticInterface
    {
        return new MonthlyStatistic($begin, $end, $user);
    }
}
