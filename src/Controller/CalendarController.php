<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Calendar\Service;
use App\Entity\Timesheet;
use App\Repository\Query\TimesheetQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller used to display calendars.
 *
 * @Route("/calendar")
 * @Security("is_granted('ROLE_USER')")
 */
class CalendarController extends AbstractController
{
    /**
     * @var Service
     */
    protected $calendar;

    /**
     * @param Service $calendar
     */
    public function __construct(Service $calendar)
    {
        $this->calendar = $calendar;
    }

    /**
     * @Route("/", name="calendar")
     * @Method("GET")
     * @Cache(smaxage="10")
     */
    public function userCalendar()
    {
        return $this->render('calendar/user.html.twig', [
            'config' => $this->calendar->getConfig(),
            'google' => $this->calendar->getGoogle()
        ]);
    }

    /**
     * @Route("/user", name="calendar_entries")
     * @Method("GET")
     * @Cache(smaxage="10")
     */
    public function calendarEntries(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        $start = \DateTime::createFromFormat('Y-m-d', $start);
        if ($start === false) {
            $start = new \DateTime('first day of this month');
        }
        $start->setTime(0, 0, 0);

        $end = \DateTime::createFromFormat('Y-m-d', $end);
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
        ;

        // running entries should only occur for the current month, but they won't
        // be found if we add the end to the query
        if ((new \DateTime())->getTimestamp() > $end->getTimestamp()) {
            $query->setEnd($end);
        }

        $repository = $this->getDoctrine()->getRepository(Timesheet::class);

        /* @var $entries Timesheet[] */
        $entries = $repository->findByQuery($query)->getQuery()->execute();
        $result = [];

        foreach ($entries as $entry) {
            $result[] = $this->getTimesheetEntryForCalendar($entry);
        }

        return $this->json($result);
    }

    /**
     * @param Timesheet $entry
     * @return array
     */
    protected function getTimesheetEntryForCalendar(Timesheet $entry)
    {
        $result = [
            'id' => $entry->getId(),
            'start' => $entry->getBegin(),
            'title' => $entry->getActivity()->getName(),
            'description' => $entry->getDescription(),
            'customer' => $entry->getActivity()->getProject()->getCustomer()->getName(),
            'project' => $entry->getActivity()->getProject()->getName(),
            'activity' => $entry->getActivity()->getName(),
        ];

        if (null === $entry->getEnd()) {
            $result['borderColor'] = '#f39c12';
            $result['backgroundColor'] = '#f39c12';
        } else {
            $result['end'] = $entry->getEnd() ?? new \DateTime();
        }

        return $result;
    }
}
