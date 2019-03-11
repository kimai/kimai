<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Timesheet;
use App\Form\TimesheetEditForm;
use App\Form\Toolbar\TimesheetToolbarForm;
use App\Repository\Query\TimesheetQuery;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used for manage timesheet entries in the admin part of the site.
 *
 * @Route(path="/team/timesheet")
 * @Security("is_granted('view_other_timesheet')")
 */
class TimesheetTeamController extends AbstractController
{
    use TimesheetControllerTrait;

    /**
     * @Route(path="/", defaults={"page": 1}, name="admin_timesheet", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_timesheet_paginated", methods={"GET"})
     * @Security("is_granted('view_other_timesheet')")
     *
     * @param $page
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
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
            if (null !== $query->getBegin()) {
                $query->getBegin()->setTime(0, 0, 0);
            }
            if (null !== $query->getEnd()) {
                $query->getEnd()->setTime(23, 59, 59);
            }
        }

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('timesheet-team/index.html.twig', [
            'entries' => $entries,
            'page' => $query->getPage(),
            'query' => $query,
            'showFilter' => $form->isSubmitted(),
            'toolbarForm' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/export", name="admin_timesheet_export", methods={"GET"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportAction(Request $request)
    {
        $query = new TimesheetQuery();
        $query->setResultType(TimesheetQuery::RESULT_TYPE_OBJECTS);

        $form = $this->getToolbarForm($query);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TimesheetQuery $query */
            $query = $form->getData();
        }

        // by default the current month is exported, but it can be overwritten
        if (null === $query->getBegin()) {
            $query->setBegin(new \DateTime('first day of this month'));
        }
        $query->getBegin()->setTime(0, 0, 0);

        if (null === $query->getEnd()) {
            $query->setEnd(new \DateTime('last day of this month'));
        }
        $query->getEnd()->setTime(23, 59, 59);

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('timesheet-team/export.html.twig', [
            'entries' => $entries,
            'query' => $query,
        ]);
    }

    /**
     * @Route(path="/{id}/stop", name="admin_timesheet_stop", methods={"GET"})
     * @Security("is_granted('stop', entry)")
     *
     * @param Timesheet $entry
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function stopAction(Timesheet $entry)
    {
        return $this->stop($entry, 'admin_timesheet');
    }

    /**
     * @Route(path="/{id}/edit", name="admin_timesheet_edit", methods={"GET", "POST"})
     * @Security("is_granted('edit', entry)")
     *
     * @param Timesheet $entry
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Timesheet $entry, Request $request)
    {
        return $this->edit($entry, $request, 'admin_timesheet_paginated', 'timesheet-team/edit.html.twig');
    }

    /**
     * @Route(path="/create", name="admin_timesheet_create", methods={"GET", "POST"})
     * @Security("is_granted('create_other_timesheet')")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        return $this->create($request, 'admin_timesheet', 'timesheet-team/edit.html.twig');
    }

    /**
     * @Route(path="/{id}/delete", defaults={"page": 1}, name="admin_timesheet_delete", methods={"GET", "POST"})
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

            $this->flashSuccess('action.delete.success');
        } catch (\Exception $ex) {
            $this->flashError('action.delete.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute('admin_timesheet_paginated', ['page' => $request->get('page', 1)]);
    }

    /**
     * @param Timesheet $entry
     * @param string $redirectRoute
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getCreateForm(Timesheet $entry, string $redirectRoute)
    {
        return $this->createForm(TimesheetEditForm::class, $entry, [
            'action' => $this->generateUrl('admin_timesheet_create'),
            'include_rate' => $this->isGranted('edit_rate', $entry),
            'include_user' => true,
        ]);
    }

    /**
     * @param Timesheet $entry
     * @param int $page
     * @param string $redirectRoute
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getEditForm(Timesheet $entry, $page, string $redirectRoute)
    {
        return $this->createForm(TimesheetEditForm::class, $entry, [
            'action' => $this->generateUrl('admin_timesheet_edit', [
                'id' => $entry->getId(),
                'page' => $page,
            ]),
            'include_rate' => $this->isGranted('edit_rate', $entry),
            'include_exported' => $this->isGranted('edit_export', $entry),
            'include_user' => true,
        ]);
    }

    /**
     * @param TimesheetQuery $query
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getToolbarForm(TimesheetQuery $query)
    {
        return $this->createForm(TimesheetToolbarForm::class, $query, [
            'action' => $this->generateUrl('admin_timesheet', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
            'include_user' => true,
        ]);
    }
}
