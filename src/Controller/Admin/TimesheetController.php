<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Controller\TagImplementationTrait;
use App\Controller\TimesheetControllerTrait;
use App\Entity\Timesheet;
use App\Form\TimesheetEditForm;
use App\Form\Toolbar\TimesheetAdminToolbarForm;
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
class TimesheetController extends AbstractController
{
    use TimesheetControllerTrait;

    use TagImplementationTrait;

    /**
     * TimesheetController constructor.
     * @param bool $durationOnly
     */
    public function __construct(bool $durationOnly)
    {
        $this->setDurationMode($durationOnly);
    }

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

        $this->prepareTagList($query);

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('admin/timesheet.html.twig', [
            'entries' => $entries,
            'page' => $page,
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
        $query->setOrder(TimesheetQuery::ORDER_ASC);

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

        return $this->render('admin/timesheet_export.html.twig', [
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
        return $this->edit($entry, $request, 'admin_timesheet_paginated', 'admin/timesheet_edit.html.twig');
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
        return $this->create($request, 'admin_timesheet', 'admin/timesheet_edit.html.twig');
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

        return $this->redirectToRoute('admin_timesheet_paginated', ['page' => $request->get('page')]);
    }

    /**
     * @param Timesheet $entry
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getCreateForm(Timesheet $entry)
    {
        return $this->createForm(TimesheetEditForm::class, $entry, [
            'action' => $this->generateUrl('admin_timesheet_create'),
            'method' => 'POST',
            'duration_only' => $this->isDurationOnlyMode(),
            'include_rate' => $this->isGranted('edit_rate', $entry),
            'include_user' => true,
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
            'action' => $this->generateUrl('admin_timesheet_edit', [
                'id' => $entry->getId(),
                'page' => $page
            ]),
            'method' => 'POST',
            'duration_only' => $this->isDurationOnlyMode(),
            'include_rate' => $this->isGranted('edit_rate', $entry),
            'include_user' => true,
        ]);
    }

    /**
     * @param TimesheetQuery $query
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getToolbarForm(TimesheetQuery $query)
    {
        return $this->createForm(TimesheetAdminToolbarForm::class, $query, [
            'action' => $this->generateUrl('admin_timesheet', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
        ]);
    }
}
