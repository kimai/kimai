<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Timesheet;
use App\Form\TimesheetEditForm;
use App\Form\Toolbar\TimesheetToolbarForm;
use App\Repository\Query\TimesheetQuery;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller used to manage timesheet contents in the public part of the site.
 *
 * @Route("/timesheet")
 * @Security("is_granted('ROLE_USER')")
 */
class TimesheetController extends AbstractController
{
    use TimesheetControllerTrait;

    /**
     * TimesheetController constructor.
     * @param bool $durationOnly
     */
    public function __construct(bool $durationOnly)
    {
        $this->setDurationMode($durationOnly);
    }

    /**
     * @Route("/", defaults={"page": 1}, name="timesheet")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="timesheet_paginated")
     * @Method("GET")
     * @Cache(smaxage="10")
     */
    public function indexAction($page, Request $request)
    {
        $query = new TimesheetQuery();
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TimesheetQuery $query */
            $query = $form->getData();
        }

        $query->setUser($this->getUser());

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('timesheet/index.html.twig', [
            'entries' => $entries,
            'page' => $page,
            'query' => $query,
            'toolbarForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/calendar", name="timesheet_calendar")
     * @Method("GET")
     * @Cache(smaxage="10")
     */
    public function calendar(Request $request)
    {
        return $this->render('timesheet/calendar.html.twig');
    }

    /**
     * @Route("/calendar/entries", name="timesheet_calendar_date")
     * @Method("GET")
     * @Cache(smaxage="10")
     */
    public function calendarEntries(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        if ($start === null) {
            $start = new \DateTime('first day of this month');
            $start->setTime(0, 0, 0);
        } else {
            $start = \DateTime::createFromFormat('Y-m-d', $start);
            if (!$start) {
                throw new \Exception('Invalid start-date given');
            }
        }

        if ($end === null) {
            $end = new \DateTime('last day of this month');
            $end->setTime(23, 59, 59);
        } else {
            $end = \DateTime::createFromFormat('Y-m-d', $end);
            if (!$end) {
                throw new \Exception('Invalid end-date given');
            }
        }

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

        /* @var $entries Timesheet[] */
        $entries = $this->getRepository()->findByQuery($query)->getQuery()->execute();
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
            'title' => $entry->getActivity()->getName() . ' (' . $entry->getActivity()->getProject()->getName() . ')',
            'description' => $entry->getDescription(),
            'customer' => $entry->getActivity()->getProject()->getCustomer()->getName(),
            'project' => $entry->getActivity()->getProject()->getName(),
            'activity' => $entry->getActivity()->getName(),
        ];

        if (null === $entry->getEnd()) {
            $result['backgroundColor'] = '#ccc';
            $result['borderColor'] = '#ccc';
        } else {
            $result['end'] = $entry->getEnd() ?? new \DateTime();
        }

        return $result;
    }

    /**
     * The "main button and flyout" for displaying (and stopping) active entries.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function activeEntriesAction()
    {
        $user = $this->getUser();
        $activeEntries = $this->getRepository()->getActiveEntries($user);

        return $this->render(
            'navbar/active-entries.html.twig',
            ['entries' => $activeEntries]
        );
    }

    /**
     * The route to stop a running entry.
     *
     * @Route("/{id}/stop", name="timesheet_stop")
     * @Method({"GET"})
     * @Security("is_granted('stop', entry)")
     *
     * @param Timesheet $entry
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function stopAction(Timesheet $entry)
    {
        return $this->stop($entry, 'timesheet');
    }

    /**
     * The route to stop a running entry.
     *
     * @Route("/start/{id}", name="timesheet_start", requirements={"id" = "\d+"})
     * @Method({"GET", "POST"})
     * @Security("is_granted('start', activity)")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function startAction(Activity $activity)
    {
        $user = $this->getUser();

        try {
            $this->getRepository()->startRecording($user, $activity);
            $this->flashSuccess('timesheet.start.success');
        } catch (\Exception $ex) {
            $this->flashError('timesheet.start.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute('timesheet');
    }

    /**
     * The route to edit an existing entry.
     *
     * @Route("/{id}/edit", name="timesheet_edit")
     * @Method({"GET", "POST"})
     * @Security("is_granted('edit', entry)")
     *
     * @param Timesheet $entry
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Timesheet $entry, Request $request)
    {
        if (null !== $request->get('page')) {
            return $this->edit($entry, $request, 'timesheet_paginated', 'timesheet/edit.html.twig');
        }
        return $this->edit($entry, $request, 'timesheet', 'timesheet/edit.html.twig');
    }

    /**
     * The route to create a new entry by form.
     *
     * @Route("/create", name="timesheet_create")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        return $this->create($request, 'timesheet', 'timesheet/edit.html.twig');
    }

    /**
     * The route to delete an existing entry.
     *
     * @Route("/{id}/delete", name="timesheet_delete")
     * @Method({"GET", "POST"})
     * @Security("is_granted('delete', entry)")
     *
     * @param Timesheet $entry
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Timesheet $entry, Request $request)
    {
        try {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($entry);
            $entityManager->flush();

            $this->flashSuccess('action.deleted_successfully');
        } catch (\Exception $ex) {
            $this->flashError('action.deleted.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute('timesheet_paginated', ['page' => $request->get('page')]);
    }

    /**
     * @param Timesheet $entry
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getCreateForm(Timesheet $entry)
    {
        return $this->createForm(TimesheetEditForm::class, $entry, [
            'action' => $this->generateUrl('timesheet_create'),
            'method' => 'POST',
            'duration_only' => $this->isDurationOnlyMode(),
        ]);
    }

    /**
     * @param Timesheet $entry
     * @param int $page
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getEditForm(Timesheet $entry, $page)
    {
        return $this->createForm(TimesheetEditForm::class, $entry, [
            'action' => $this->generateUrl('timesheet_edit', [
                'id' => $entry->getId(),
                'page' => $page
            ]),
            'method' => 'POST',
            'duration_only' => $this->isDurationOnlyMode(),
        ]);
    }

    /**
     * @param TimesheetQuery $query
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getToolbarForm(TimesheetQuery $query)
    {
        return $this->createForm(TimesheetToolbarForm::class, $query, [
            'action' => $this->generateUrl('timesheet', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
        ]);
    }
}
