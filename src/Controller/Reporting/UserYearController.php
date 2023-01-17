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
use App\Model\DateStatisticInterface;
use App\Model\MonthlyStatistic;
use App\Reporting\YearByUser\YearByUser;
use App\Reporting\YearByUser\YearByUserForm;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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

        $start = $values->getDate();
        // there is a potential edge case bug for financial years:
        // the last month will be skipped, if the financial year started on a different day than the first
        $end = $dateTimeFactory->createEndOfFinancialYear($start);

        $selectedUser = $values->getUser();

        $previous = clone $start;
        $previous->modify('-1 year');

        $next = clone $start;
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
        ];
    }

    protected function getStatisticDataRaw(DateTime $begin, DateTime $end, User $user): array
    {
        return $this->statisticService->getMonthlyStatisticsGrouped($begin, $end, [$user]);
    }

    protected function createStatisticModel(DateTime $begin, DateTime $end, User $user): DateStatisticInterface
    {
        return new MonthlyStatistic($begin, $end, $user);
    }
}
