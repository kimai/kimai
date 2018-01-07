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
use TimesheetBundle\Entity\Customer;
use TimesheetBundle\Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use TimesheetBundle\Form\ProjectEditForm;
use TimesheetBundle\Repository\Query\ProjectQuery;

/**
 * Controller used to manage projects in the admin part of the site.
 *
 * @Route("/admin/project")
 * @Security("has_role('ROLE_ADMIN')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProjectController extends AbstractController
{
    /**
     * @Route("/", defaults={"page": 1}, name="admin_project")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_project_paginated")
     * @Method("GET")
     * @Cache(smaxage="10")
     */
    public function indexAction($page)
    {
        $query = new ProjectQuery();
        $query->setVisibility(ProjectQuery::SHOW_BOTH);
        $query->setPage($page);

        /* @var $entries Pagerfanta */
        $entries = $this->getDoctrine()->getRepository(Project::class)->findByQuery($query);

        return $this->render('TimesheetBundle:admin:project.html.twig', ['entries' => $entries]);
    }

    /**
     * @Route("/create", name="admin_project_create")
     * @Method({"GET", "POST"})
     */
    public function createAction(Request $request)
    {
        return $this->renderProjectForm(new Project(), $request);
    }

    /**
     * @Route("/{id}/edit", name="admin_project_edit")
     * @Method({"GET", "POST"})
     * @Security("is_granted('edit', project)")
     */
    public function editAction(Project $project, Request $request)
    {
        return $this->renderProjectForm($project, $request);
    }

    /**
     * @param Project $project
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function renderProjectForm(Project $project, Request $request)
    {
        $editForm = $this->createEditForm($project);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($project);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute('admin_project', ['id' => $project->getId()]);
        }

        return $this->render(
            'TimesheetBundle:admin:project_edit.html.twig',
            [
                'project' => $project,
                'form' => $editForm->createView()
            ]
        );
    }

    /**
     * @param Project $project
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createEditForm(Project $project)
    {
        if ($project->getId() === null) {
            $url = $this->generateUrl('admin_project_create');
            $currency = Customer::DEFAULT_CURRENCY;
        } else {
            $url = $this->generateUrl('admin_project_edit', ['id' => $project->getId()]);
            $currency = $project->getCustomer()->getCurrency();
        }

        return $this->createForm(
            ProjectEditForm::class,
            $project,
            [
                'action' => $url,
                'method' => 'POST',
                'currency' => $currency,
            ]
        );
    }
}
