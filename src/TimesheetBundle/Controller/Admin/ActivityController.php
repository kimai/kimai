<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Controller\Admin;

use AppBundle\Controller\AbstractController;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use TimesheetBundle\Entity\Activity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use TimesheetBundle\Entity\Customer;
use TimesheetBundle\Entity\Project;
use TimesheetBundle\Form\ActivityEditForm;
use TimesheetBundle\Form\ActivityToolbarForm;
use TimesheetBundle\Repository\Query\ActivityQuery;

/**
 * Controller used to manage activities in the admin part of the site.
 *
 * @Route("/admin/activity")
 * @Security("has_role('ROLE_ADMIN')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ActivityController extends AbstractController
{
    /**
     * @param Request $request
     * @return ActivityQuery
     */
    protected function getQueryForRequest(Request $request)
    {
        $visibility = $request->get('visibility');
        if (strlen($visibility) == 0 || (int)$visibility != $visibility) {
            $visibility = ActivityQuery::SHOW_BOTH;
        }
        $pageSize = (int) $request->get('pageSize');
        $customer = $request->get('customer');
        $customer = !empty(trim($customer)) ? trim($customer) : null;
        $project = $request->get('project');
        $project = !empty(trim($project)) ? trim($project) : null;

        if ($project !== null) {
            $repo = $this->getDoctrine()->getRepository(Project::class);
            $project = $repo->getById($project);
            if ($project !== null) {
                $customer = $project->getCustomer();
            } else {
                $customer = null;
            }
        } elseif ($customer !== null) {
            $repo = $this->getDoctrine()->getRepository(Customer::class);
            $customer = $repo->getById($customer);
        }

        $query = new ActivityQuery();
        $query
            ->setPageSize($pageSize)
            ->setVisibility($visibility)
            ->setCustomer($customer)
            ->setProject($project)
        ;

        return $query ;
    }

    /**
     * @Route("/", defaults={"page": 1}, name="admin_activity")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_activity_paginated")
     * @Method("GET")
     * @Cache(smaxage="10")
     */
    public function indexAction($page, Request $request)
    {
        $query = $this->getQueryForRequest($request);
        $query->setPage($page);

        /* @var $entries Pagerfanta */
        $entries = $this->getDoctrine()->getRepository(Activity::class)->findByQuery($query);

        return $this->render('TimesheetBundle:admin:activity.html.twig', [
            'entries' => $entries,
            'query' => $query,
            'toolbarForm' => $this->getToolbarForm($query)->createView(),
        ]);
    }

    /**
     * @Route("/create", name="admin_activity_create")
     * @Method({"GET", "POST"})
     */
    public function createAction(Request $request)
    {
        return $this->renderActivityForm(new Activity(), $request);
    }

    /**
     * @Route("/{id}/edit", name="admin_activity_edit")
     * @Method({"GET", "POST"})
     * @Security("is_granted('edit', activity)")
     */
    public function editAction(Activity $activity, Request $request)
    {
        return $this->renderActivityForm($activity, $request);
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

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute('admin_activity', ['id' => $activity->getId()]);
        }

        return $this->render(
            'TimesheetBundle:admin:activity_edit.html.twig',
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
        return $this->createForm(
            ActivityToolbarForm::class,
            $query,
            [
                'action' => $this->generateUrl('admin_activity_paginated', [
                    'page' => $query->getPage(),
                ]),
                'method' => 'GET',
            ]
        );
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

        return $this->createForm(
            ActivityEditForm::class,
            $activity,
            [
                'action' => $url,
                'method' => 'POST'
            ]
        );
    }
}
