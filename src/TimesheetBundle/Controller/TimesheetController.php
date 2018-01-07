<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Controller;

use AppBundle\Controller\AbstractController;
use Pagerfanta\Pagerfanta;
use TimesheetBundle\Entity\Activity;
use TimesheetBundle\Entity\Customer;
use TimesheetBundle\Entity\Timesheet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use TimesheetBundle\Form\TimesheetEditForm;

/**
 * Controller used to manage timesheet contents in the public part of the site.
 *
 * @Route("/timesheet")
 * @Security("has_role('ROLE_USER')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetController extends AbstractController
{
    use TimesheetControllerTrait;

    /**
     * @Route("/", defaults={"page": 1}, name="timesheet")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="timesheet_paginated")
     * @Method("GET")
     * @Cache(smaxage="10")
     */
    public function indexAction($page, Request $request)
    {
        $query = $this->getQueryForRequest($request);
        $query->setUser($this->getUser());
        $query->setPage($page);

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('TimesheetBundle:timesheet:index.html.twig', [
            'entries' => $entries,
            'page' => $page,
            'query' => $query,
            'toolbarForm' => $this->getToolbarForm($query)->createView(),
        ]);
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
            'TimesheetBundle:Navbar:active-entries.html.twig',
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
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function stopAction(Timesheet $entry, Request $request)
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
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function startAction(Activity $activity, Request $request)
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
        return $this->edit($entry, $request, 'timesheet_paginated', 'TimesheetBundle:timesheet:edit.html.twig');
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
        return $this->create($request, 'timesheet', 'TimesheetBundle:timesheet:edit.html.twig');
    }

    /**
     * @param Timesheet $entry
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getCreateForm(Timesheet $entry)
    {
        return $this->createForm(
            TimesheetEditForm::class,
            $entry,
            [
                'action' => $this->generateUrl('timesheet_create'),
                'method' => 'POST',
                'currency' => Customer::DEFAULT_CURRENCY,
            ]
        );
    }

    /**
     * @param Timesheet $entry
     * @param int $page
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getEditForm(Timesheet $entry, $page)
    {
        return $this->createForm(
            TimesheetEditForm::class,
            $entry,
            [
                'action' => $this->generateUrl('timesheet_edit', [
                    'id' => $entry->getId(),
                    'page' => $page
                ]),
                'method' => 'POST',
                'currency' => $entry->getActivity()->getProject()->getCustomer()->getCurrency(),
            ]
        );
    }
}
