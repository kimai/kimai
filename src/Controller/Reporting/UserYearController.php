<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Reporting;

use App\Entity\User;
use App\Model\DateStatisticInterface;
use App\Model\MonthlyStatistic;
use App\Reporting\YearByUser;
use App\Reporting\YearByUserForm;
use DateTime;
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
final class UserYearController extends AbstractUserReportController
{
    /**
     * @Route(path="/year", name="report_user_year", methods={"GET","POST"})
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function yearByUser(Request $request): Response
    {
        return $this->render('reporting/report_by_user_year.html.twig', $this->getData($request));
    }

    private function getData(Request $request): array
    {
        $currentUser = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory($currentUser);
        $canChangeUser = $this->canSelectUser();

        $values = new YearByUser();
        $values->setUser($currentUser);
        $values->setDate($dateTimeFactory->createStartOfYear());

        $form = $this->createForm(YearByUserForm::class, $values, [
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
            $values->setDate($dateTimeFactory->createStartOfYear());
        }

        $start = $dateTimeFactory->createStartOfYear($values->getDate());
        $end = $dateTimeFactory->createEndOfYear($values->getDate());
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
