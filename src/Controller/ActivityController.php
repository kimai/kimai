<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Project;
use App\Form\ActivityEditForm;
use App\Form\Toolbar\ActivityToolbarForm;
use App\Form\Type\ActivityType;
use App\Repository\ActivityRepository;
use App\Repository\Query\ActivityQuery;
use Doctrine\ORM\ORMException;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage activities in the admin part of the site.
 *
 * @Route(path="/admin/activity")
 * @Security("is_granted('view_activity')")
 */
class ActivityController extends AbstractController
{
    /**
     * @var ActivityRepository
     */
    private $repository;

    public function __construct(ActivityRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): ActivityRepository
    {
        return $this->repository;
    }

    /**
     * @Route(path="/", defaults={"page": 1}, name="admin_activity", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_activity_paginated", methods={"GET"})
     * @Security("is_granted('view_activity')")
     *
     * @param int $page
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page, Request $request)
    {
        $query = new ActivityQuery();
        $query
            ->setOrderBy('name')
            ->setExclusiveVisibility(true)
            ->setPage($page)
        ;

        $form = $this->getToolbarForm($query);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ActivityQuery $query */
            $query = $form->getData();
        }

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('activity/index.html.twig', [
            'entries' => $entries,
            'query' => $query,
            'showFilter' => $form->isSubmitted(),
            'toolbarForm' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/create", name="admin_activity_create", methods={"GET", "POST"})
     * @Route(path="/create/{project}", name="admin_activity_create_with_project", methods={"GET", "POST"})
     * @Security("is_granted('create_activity')")
     *
     * @param Request $request
     * @param Project|null $project
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request, ?Project $project = null)
    {
        $activity = new Activity();
        if (null !== $project) {
            $activity->setProject($project);
        }

        return $this->renderActivityForm($activity, $request);
    }

    /**
     * @Route(path="/{id}/budget", name="admin_activity_budget", methods={"GET"})
     * @Security("is_granted('budget', activity)")
     *
     * @param Activity $activity
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function budgetAction(Activity $activity)
    {
        return $this->render('activity/budget.html.twig', [
            'activity' => $activity,
            'stats' => $this->getRepository()->getActivityStatistics($activity)
        ]);
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

        $deleteForm = $this->createFormBuilder(null, [
                'attr' => [
                    'data-form-event' => 'kimai.activityUpdate kimai.activityDelete',
                    'data-msg-success' => 'action.delete.success',
                    'data-msg-error' => 'action.delete.error',
                ]
            ])
            ->add('activity', ActivityType::class, [
                'label' => 'label.activity',
                'query_builder' => function (ActivityRepository $repo) use ($activity) {
                    $query = new ActivityQuery();
                    $query
                        ->setResultType(ActivityQuery::RESULT_TYPE_QUERYBUILDER)
                        ->setProject($activity->getProject())
                        ->setOrderGlobalsFirst(true)
                        ->addIgnoredEntity($activity)
                        ->setGlobalsOnly(null === $activity->getProject())
                    ;

                    return $repo->findByQuery($query);
                },
                'required' => false,
            ])
            ->setAction($this->generateUrl('admin_activity_delete', ['id' => $activity->getId()]))
            ->setMethod('POST')
            ->getForm();

        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            try {
                $this->getRepository()->deleteActivity($activity, $deleteForm->get('activity')->getData());
                $this->flashSuccess('action.delete.success');
            } catch (ORMException $ex) {
                $this->flashError('action.delete.error', ['%reason%' => $ex->getMessage()]);
            }

            return $this->redirectToRoute('admin_activity');
        }

        return $this->render(
            'activity/delete.html.twig',
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
            'activity/edit.html.twig',
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
            'method' => 'POST',
            'create_more' => true,
            'customer' => true,
            'include_budget' => $this->isGranted('budget', $activity)
        ]);
    }
}
