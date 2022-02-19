<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Entity\User;
use App\Model\DailyStatistic;
use App\Reporting\WeekByUser;
use App\Reporting\WeekByUserForm;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route(path="/reporting/user")
 * @Security("is_granted('view_reporting')")
 */
final class UserWeekController extends AbstractUserReportController
{
    /**
     * @Route(path="/week", name="report_user_week", methods={"GET","POST"})
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function weekByUser(Request $request): Response
    {
        return $this->render('reporting/report_by_user.html.twig', $this->getData($request));
    }

    private function getData(Request $request): array
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory($currentUser);
        $canChangeUser = $this->canSelectUser();

        $values = new WeekByUser();
        $values->setUser($currentUser);
        $values->setDate($dateTimeFactory->getStartOfWeek());

        $form = $this->createForm(WeekByUserForm::class, $values, [
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
            $values->setDate($dateTimeFactory->getStartOfWeek());
        }

        $start = $dateTimeFactory->getStartOfWeek($values->getDate());
        $end = $dateTimeFactory->getEndOfWeek($values->getDate());
        $selectedUser = $values->getUser();

        $previous = clone $start;
        $previous->modify('-1 week');

        $next = clone $start;
        $next->modify('+1 week');

        $data = $this->prepareReport($start, $end, $selectedUser);

        return [
            'decimal' => $values->isDecimal(),
            'dataType' => $values->getSumType(),
            'report_title' => 'report_user_week',
            'box_id' => 'user-week-reporting-box',
            'form' => $form->createView(),
            'period' => new DailyStatistic($start, $end, $selectedUser),
            'rows' => $data,
            'user' => $selectedUser,
            'current' => $start,
            'next' => $next,
            'previous' => $previous,
        ];
    }
}
