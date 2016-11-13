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

use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use TimesheetBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use TimesheetBundle\Form\ProjectEditForm;
use TimesheetBundle\Repository\ProjectRepository;

/**
 * Controller used to manage projects in the admin part of the site.
 *
 * @Route("/admin/project")
 * @Security("has_role('ROLE_ADMIN')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProjectController extends Controller
{
    /**
     * @Route("/", defaults={"page": 1}, name="admin_project")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_project_paginated")
     * @Method("GET")
     * @Cache(smaxage="10")
     *
     * @param $page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page)
    {
        /* @var $entries Pagerfanta */
        $entries = $this->getDoctrine()->getRepository(Project::class)->findAll($page);

        return $this->render('TimesheetBundle:admin:project.html.twig', ['entries' => $entries]);
    }

    /**
     * @Route("/{id}/edit", name="admin_project_edit")
     * @Method({"GET", "POST"})
     *
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction($id, Request $request)
    {
        $project = $this->getById($id);
        $editForm = $this->createEditForm($project);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'action.updated_successfully');

            return $this->redirectToRoute(
                'admin_project', ['id' => $project->getId()]
            );
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
     * @param $id
     * @return null|Project
     */
    protected function getById($id)
    {
        /* @var $repo ProjectRepository */
        $repo = $this->getDoctrine()->getRepository(Project::class);
        $activity = $repo->getById($id);
        if (null === $activity) {
            throw new NotFoundHttpException('Project "'.$id.'" does not exist');
        }
        return $activity;
    }

    /**
     * @param Project $project
     * @return \Symfony\Component\Form\Form
     */
    private function createEditForm(Project $project)
    {
        return $this->createForm(
            ProjectEditForm::class,
            $project,
            [
                'action' => $this->generateUrl('admin_project_edit', ['id' => $project->getId()]),
                'method' => 'POST',
                'currency' => $project->getCurrency()
            ]
        );
    }
}
