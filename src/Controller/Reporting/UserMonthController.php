<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Model\DailyStatistic;
use App\Reporting\MonthByUser\MonthByUser;
use App\Reporting\MonthByUser\MonthByUserForm;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/reporting/user')]
#[IsGranted('report:user')]
final class UserMonthController extends AbstractUserReportController
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route(path: '/month', name: 'report_user_month', methods: ['GET', 'POST'])]
    public function monthByUser(Request $request): Response
    {
        return $this->render('reporting/report_by_user.html.twig', $this->getData($request));
    }

    private function getData(Request $request): array
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory($currentUser);
        $canChangeUser = $this->canSelectUser();

        $values = new MonthByUser();
        $values->setUser($currentUser);
        $values->setDate($dateTimeFactory->getStartOfMonth());

        $form = $this->createFormForGetRequest(MonthByUserForm::class, $values, [
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
            $values->setDate($dateTimeFactory->getStartOfMonth());
        }

        $start = $values->getDate();
        $start->modify('first day of 00:00:00');

        $end = clone $start;
        $end->modify('last day of 23:59:59');

        $selectedUser = $values->getUser();

        $previousMonth = clone $start;
        $previousMonth->modify('-1 month');

        $nextMonth = clone $start;
        $nextMonth->modify('+1 month');

        $data = $this->prepareReport($start, $end, $selectedUser);

        return [
            'decimal' => $values->isDecimal(),
            'dataType' => $values->getSumType(),
            'report_title' => 'report_user_month',
            'box_id' => 'user-month-reporting-box',
            'form' => $form->createView(),
            'rows' => $data,
            'period' => new DailyStatistic($start, $end, $selectedUser),
            'user' => $selectedUser,
            'current' => $start,
            'next' => $nextMonth,
            'previous' => $previousMonth,
        ];
    }
}
