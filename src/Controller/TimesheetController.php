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
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Controller used to manage timesheets.
 *
 * @Route(path="/timesheet")
 * @Security("is_granted('view_own_timesheet')")
 */
class TimesheetController extends AbstractController
{
    use TimesheetControllerTrait;

    /**
     * @param int $hardLimit
     */
    public function __construct(int $hardLimit)
    {
        $this->setHardLimit($hardLimit);
    }

    /**
     * @Route(path="/", defaults={"page": 1}, name="timesheet", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="timesheet_paginated", methods={"GET"})
     * @Security("is_granted('view_own_timesheet')")
     *
     * @param int $page
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

        $query->setUser($this->getUser());

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('timesheet/index.html.twig', [
            'entries' => $entries,
            'page' => $query->getPage(),
            'query' => $query,
            'showFilter' => $form->isSubmitted(),
            'toolbarForm' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/export", name="timesheet_export", methods={"GET"})
     * @Security("is_granted('export_own_timesheet')")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportAction(Request $request)
    {
        $query = new TimesheetQuery();

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

        $query->setUser($this->getUser());

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('timesheet/export.html.twig', [
            'entries' => $entries,
            'query' => $query,
        ]);
    }

    /**
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
     * @Route(path="/{id}/stop", name="timesheet_stop", methods={"GET"})
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
     * @Route(path="/start/{id}", name="timesheet_start", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     * @Security("is_granted('start', timesheet)")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function startAction(ValidatorInterface $validator, Timesheet $timesheet)
    {
        $user = $this->getUser();

        try {
            $entry = new Timesheet();
            $entry
                ->setBegin(new \DateTime())
                ->setUser($user)
                ->setActivity($timesheet->getActivity())
                ->setProject($timesheet->getProject())
            ;

            $errors = $validator->validate($entry);

            if (count($errors) > 0) {
                $this->flashError('timesheet.start.error', ['%reason%' => $errors[0]->getPropertyPath() . ' = ' . $errors[0]->getMessage()]);
            } else {
                $this->stopActiveEntries($user);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($entry);
                $entityManager->flush();

                $this->flashSuccess('timesheet.start.success');
            }
        } catch (\Exception $ex) {
            $this->flashError('timesheet.start.error', ['%reason%' => $ex->getMessage()]);
        }

        return $this->redirectToRoute('timesheet');
    }

    /**
     * @Route(path="/{id}/edit", name="timesheet_edit", methods={"GET", "POST"})
     * @Security("is_granted('edit', entry)")
     *
     * @param Timesheet $entry
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Timesheet $entry, Request $request)
    {
        $route = 'timesheet';

        if (null !== $request->get('page')) {
            $route = 'timesheet_paginated';
        } elseif ('calendar' === $request->get('origin')) {
            $route = 'calendar';
        }

        return $this->edit($entry, $request, $route, 'timesheet/edit.html.twig');
    }

    /**
     * @Route(path="/create", name="timesheet_create", methods={"GET", "POST"})
     * @Security("is_granted('create_own_timesheet')")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        $route = 'timesheet';
        if ('calendar' === $request->get('origin')) {
            $route = 'calendar';
        }

        return $this->create($request, $route, 'timesheet/edit.html.twig');
    }

    /**
     * @Route(path="/{id}/delete", defaults={"page": 1}, name="timesheet_delete", methods={"GET", "POST"})
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

        return $this->redirectToRoute('timesheet_paginated', ['page' => $request->get('page')]);
    }

    /**
     * @param Timesheet $entry
     * @param string $redirectRoute
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getCreateForm(Timesheet $entry, string $redirectRoute)
    {
        return $this->createForm(TimesheetEditForm::class, $entry, [
            'action' => $this->generateUrl('timesheet_create', ['origin' => $redirectRoute]),
            'include_rate' => $this->isGranted('edit_rate', $entry),
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
            'action' => $this->generateUrl('timesheet_edit', [
                'id' => $entry->getId(),
                'page' => $page,
                'origin' => $redirectRoute,
            ]),
            'include_rate' => $this->isGranted('edit_rate', $entry),
            'include_exported' => $this->isGranted('edit_export', $entry),
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
