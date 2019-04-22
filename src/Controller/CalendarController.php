<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Calendar\Service;
use App\Calendar\TimesheetEntity;
use App\Entity\Timesheet;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
use App\Timesheet\UserDateTimeFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to display calendars.
 *
 * @Route(path="/calendar")
 * @Security("is_granted('ROLE_USER')")
 */
class CalendarController extends AbstractController
{
    /**
     * @Route(path="/", name="calendar", methods={"GET"})
     * @Cache(smaxage="10")
     */
    public function userCalendar(Service $calendar, UserDateTimeFactory $dateTime)
    {
        return $this->render('calendar/user.html.twig', [
            'config' => $calendar->getConfig(),
            'google' => $calendar->getGoogle(),
            'now' => $dateTime->createDateTime(),
        ]);
    }

    /**
     * @Route(path="/user", name="calendar_entries", methods={"GET"})
     * @Cache(smaxage="10")
     */
    public function calendarEntries(Request $request, UserDateTimeFactory $dateTime, TimesheetRepository $repository)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        $start = $dateTime->createDateTimeFromFormat('Y-m-d', $start);
        if ($start === false) {
            $start = $dateTime->createDateTime('first day of this month');
        }
        $start->setTime(0, 0, 0);

        $end = $dateTime->createDateTimeFromFormat('Y-m-d', $end);
        if ($end === false) {
            $end = clone $start;
            $end = $end->modify('last day of this month');
        }
        $end->setTime(23, 59, 59);

        $query = new TimesheetQuery();
        $query
            ->setBegin($start)
            ->setUser($this->getUser())
            ->setState(TimesheetQuery::STATE_ALL)
            ->setResultType(TimesheetQuery::RESULT_TYPE_QUERYBUILDER)
            ->setEnd($end)
        ;

        /* @var $entries Timesheet[] */
        $entries = $repository->findByQuery($query)->getQuery()->execute();
        $result = [];

        foreach ($entries as $entry) {
            $result[] = new TimesheetEntity($entry);
        }

        return $this->json($result);
    }
}
