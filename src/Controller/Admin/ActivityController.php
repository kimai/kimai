<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Activity;
use App\Form\ActivityEditForm;
use App\Form\Toolbar\ActivityToolbarForm;
use App\Repository\Query\ActivityQuery;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage activities in the admin part of the site.
 *
 * @Route(path="/admin/activity")
 * @Security("is_granted('ROLE_ADMIN')")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class ActivityController extends AbstractController
{
    /**
     * @return \App\Repository\ActivityRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository(Activity::class);
    }

    /**
     * @Route(path="/", defaults={"page": 1}, name="admin_activity", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_activity_paginated", methods={"GET"})
     * @Cache(smaxage="10")
     */
    public function indexAction($page, Request $request)
    {
        $query = new ActivityQuery();
        $query->setExclusiveVisibility(true);
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ActivityQuery $query */
            $query = $form->getData();
        }

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('admin/activity.html.twig', [
            'entries' => $entries,
            'query' => $query,
            'toolbarForm' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/create", name="admin_activity_create", methods={"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        return $this->renderActivityForm(new Activity(), $request);
    }

    /**
     * @Route(path="/{id}/edit", name="admin_activity_edit", methods={"GET", "POST"})
     * @Security("is_granted('edit', activity)")
     *
     * @param Activity $activity
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Activity $activity, Request $request)
    {
        return $this->renderActivityForm($activity, $request);
    }

    /**
     * The route to delete an existing entry.
     *
     * @Route(path="/{id}/delete", name="admin_activity_delete", methods={"GET", "POST"})
     * @Security("is_granted('delete', activity)")
     *
     * @param Activity $activity
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Activity $activity, Request $request)
    {
        $stats = $this->getRepository()->getActivityStatistics($activity);

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_activity_delete', ['id' => $activity->getId()]))
            ->setMethod('POST')
            ->getForm();

        $deleteForm->handleRequest($request);

        if (0 == $stats->getRecordAmount() || ($deleteForm->isSubmitted() && $deleteForm->isValid())) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($activity);
            $entityManager->flush();

            $this->flashSuccess('action.delete.success');

            return $this->redirectToRoute('admin_activity');
        }

        return $this->render(
            'admin/activity_delete.html.twig',
            [
                'activity' => $activity,
                'stats' => $stats,
                'form' => $deleteForm->createView(),
            ]
        );
    }

    /**
     * @param Activity $activity
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function renderActivityForm(Activity $activity, Request $request)
    {
        $editForm = $this->createEditForm($activity);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($activity);
            $entityManager->flush();

            $this->flashSuccess('action.update.success');

            if ($editForm->has('create_more') && $editForm->get('create_more')->getData() === true) {
                $newActivity = new Activity();
                $newActivity->setProject($activity->getProject());
                $editForm = $this->createEditForm($newActivity);
                $editForm->get('create_more')->setData(true);
                $activity = $newActivity;
            } else {
                return $this->redirectToRoute('admin_activity');
            }
        }

        return $this->render(
            'admin/activity_edit.html.twig',
            [
                'activity' => $activity,
                'form' => $editForm->createView()
            ]
        );
    }

    /**
     * @param ActivityQuery $query
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getToolbarForm(ActivityQuery $query)
    {
        return $this->createForm(ActivityToolbarForm::class, $query, [
            'action' => $this->generateUrl('admin_activity', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
        ]);
    }

    /**
     * @param Activity $activity
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createEditForm(Activity $activity)
    {
        if ($activity->getId() === null) {
            $url = $this->generateUrl('admin_activity_create');
        } else {
            $url = $this->generateUrl('admin_activity_edit', ['id' => $activity->getId()]);
        }

        return $this->createForm(ActivityEditForm::class, $activity, [
            'action' => $url,
            'method' => 'POST'
        ]);
    }
}
