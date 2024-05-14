<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Export\Spreadsheet\Writer\BinaryFileResponseWriter;
use App\Export\Spreadsheet\Writer\XlsxWriter;
use App\Model\DateStatisticInterface;
use App\Model\MonthlyStatistic;
use App\Reporting\YearByUser\YearByUser;
use App\Reporting\YearByUser\YearByUserForm;
use DateTime;
use DateTimeInterface;
use Exception;
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
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route(path: '/year', name: 'report_user_year', methods: ['GET', 'POST'])]
    public function yearByUser(Request $request, SystemConfiguration $systemConfiguration): Response
    {
        return $this->render('reporting/report_by_user_year.html.twig', $this->getData($request, $systemConfiguration));
    }

    #[Route(path: '/year_export', name: 'report_user_year_export', methods: ['GET', 'POST'])]
    public function export(Request $request, SystemConfiguration $systemConfiguration): Response
    {
        $data = $this->getData($request, $systemConfiguration);

        $content = $this->renderView('reporting/report_by_user_year_export.html.twig', $data);

        $reader = new Html();
        $spreadsheet = $reader->loadFromString($content);

        $writer = new BinaryFileResponseWriter(new XlsxWriter(), 'kimai-export-user-yearly');

        return $writer->getFileResponse($spreadsheet);
    }

    private function getData(Request $request, SystemConfiguration $systemConfiguration): array
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory($currentUser);
        $canChangeUser = $this->canSelectUser();

        $values = new YearByUser();
        $values->setUser($currentUser);

        $defaultDate = $dateTimeFactory->createStartOfYear();

        if (null !== ($financialYear = $systemConfiguration->getFinancialYearStart())) {
            $defaultDate = $this->getDateTimeFactory()->createStartOfFinancialYear($financialYear);
        }

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

        /** @var \DateTimeInterface $start */
        $start = $values->getDate();
        // there is a potential edge case bug for financial years:
        // the last month will be skipped, if the financial year started on a different day than the first
        $end = $dateTimeFactory->createEndOfFinancialYear($start);

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
        return $this->statisticService->getMonthlyStatisticsGrouped($begin, $end, [$user]);
    }

    protected function createStatisticModel(DateTimeInterface $begin, DateTimeInterface $end, User $user): DateStatisticInterface
    {
        return new MonthlyStatistic($begin, $end, $user);
    }
}
