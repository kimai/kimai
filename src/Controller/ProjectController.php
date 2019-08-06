<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Project;
use App\Event\ProjectMetaDefinitionEvent;
use App\Form\ProjectEditForm;
use App\Form\ProjectTeamPermissionForm;
use App\Form\Toolbar\ProjectToolbarForm;
use App\Form\Type\ProjectType;
use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectFormTypeQuery;
use App\Repository\Query\ProjectQuery;
use Doctrine\ORM\ORMException;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage projects in the admin part of the site.
 *
 * @Route(path="/admin/project")
 * @Security("is_granted('view_project')")
 */
class ProjectController extends AbstractController
{
    /**
     * @var ProjectRepository
     */
    private $repository;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(ProjectRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    protected function getRepository(): ProjectRepository
    {
        return $this->repository;
    }

    /**
     * @Route(path="/", defaults={"page": 1}, name="admin_project", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_project_paginated", methods={"GET"})
     * @Security("is_granted('view_project')")
     */
    public function indexAction($page, Request $request)
    {
        $query = new ProjectQuery();
        $query->setCurrentUser($this->getUser());
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->setData($query);
        $form->submit($request->query->all(), false);

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->getPagerfantaForQuery($query);

        return $this->render('project/index.html.twig', [
            'entries' => $entries,
            'query' => $query,
            'showFilter' => $query->isDirty(),
            'toolbarForm' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/{id}/permissions", name="admin_project_permissions", methods={"GET", "POST"})
     * @Security("is_granted('permissions', project)")
     */
    public function teamPermissions(Project $project, Request $request)
    {
        $form = $this->createForm(ProjectTeamPermissionForm::class, $project, [
            'action' => $this->generateUrl('admin_project_permissions', ['id' => $project->getId()]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->getRepository()->saveProject($project);
                $this->flashSuccess('action.update.success');

                return $this->redirectToRoute('admin_project');
            } catch (ORMException $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('project/permissions.html.twig', [
            'project' => $project,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route(path="/create", name="admin_project_create", methods={"GET", "POST"})
     * @Route(path="/create/{customer}", name="admin_project_create_with_customer", methods={"GET", "POST"})
     * @Security("is_granted('create_project')")
     */
    public function createAction(Request $request, ?Customer $customer = null)
    {
        $project = new Project();

        if (null !== $customer) {
            $project->setCustomer($customer);
        }

        return $this->renderProjectForm($project, $request);
    }

    /**
     * @Route(path="/{id}/budget", name="admin_project_budget", methods={"GET"})
     * @Security("is_granted('budget', project)")
     */
    public function budgetAction(Project $project)
    {
        return $this->render('project/budget.html.twig', [
            'project' => $project,
            'stats' => $this->getRepository()->getProjectStatistics($project)
        ]);
    }

    /**
     * @Route(path="/{id}/edit", name="admin_project_edit", methods={"GET", "POST"})
     * @Security("is_granted('edit', project)")
     */
    public function editAction(Project $project, Request $request)
    {
        return $this->renderProjectForm($project, $request);
    }

    /**
     * @Route(path="/{id}/delete", name="admin_project_delete", methods={"GET", "POST"})
     * @Security("is_granted('delete', project)")
     */
    public function deleteAction(Project $project, Request $request)
    {
        $stats = $this->getRepository()->getProjectStatistics($project);

        $deleteForm = $this->createFormBuilder(null, [
                'attr' => [
                    'data-form-event' => 'kimai.projectUpdate kimai.projectDelete',
                    'data-msg-success' => 'action.delete.success',
                    'data-msg-error' => 'action.delete.error',
                ]
            ])
            ->add('project', ProjectType::class, [
                'label' => 'label.project',
                'query_builder' => function (ProjectRepository $repo) use ($project) {
                    $query = new ProjectFormTypeQuery();
                    $query->setCustomer($project->getCustomer());
                    $query->setProjectToIgnore($project);
                    $query->setUser($this->getUser());

                    return $repo->getQueryBuilderForFormType($query);
                },
                'required' => false,
            ])
            ->setAction($this->generateUrl('admin_project_delete', ['id' => $project->getId()]))
            ->setMethod('POST')
            ->getForm();

        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            try {
                $this->getRepository()->deleteProject($project, $deleteForm->get('project')->getData());
                $this->flashSuccess('action.delete.success');
            } catch (ORMException $ex) {
                $this->flashError('action.delete.error', ['%reason%' => $ex->getMessage()]);
            }

            return $this->redirectToRoute('admin_project');
        }

        return $this->render('project/delete.html.twig', [
            'project' => $project,
            'stats' => $stats,
            'form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @param Project $project
     * @param Request $request
     * @return RedirectResponse|Response
     */
    protected function renderProjectForm(Project $project, Request $request)
    {
        $event = new ProjectMetaDefinitionEvent($project);
        $this->dispatcher->dispatch($event, ProjectMetaDefinitionEvent::class);

        $editForm = $this->createEditForm($project);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            try {
                $this->getRepository()->saveProject($project);
                $this->flashSuccess('action.update.success');

                if ($editForm->has('create_more') && $editForm->get('create_more')->getData() === true) {
                    $newProject = new Project();
                    $newProject->setCustomer($project->getCustomer());
                    $editForm = $this->createEditForm($newProject);
                    $editForm->get('create_more')->setData(true);
                    $project = $newProject;
                } else {
                    return $this->redirectToRoute('admin_project');
                }
            } catch (ORMException $ex) {
                $this->flashError('action.update.error', ['%reason%' => $ex->getMessage()]);
            }
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $editForm->createView()
        ]);
    }

    protected function getToolbarForm(ProjectQuery $query): FormInterface
    {
        return $this->createForm(ProjectToolbarForm::class, $query, [
            'action' => $this->generateUrl('admin_project', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
        ]);
    }

    private function createEditForm(Project $project): FormInterface
    {
        if ($project->getId() === null) {
            $url = $this->generateUrl('admin_project_create');
            $currency = Customer::DEFAULT_CURRENCY;
        } else {
            $url = $this->generateUrl('admin_project_edit', ['id' => $project->getId()]);
            $currency = $project->getCustomer()->getCurrency();
        }

        return $this->createForm(ProjectEditForm::class, $project, [
            'action' => $url,
            'method' => 'POST',
            'currency' => $currency,
            'create_more' => true,
            'include_budget' => $this->isGranted('budget', $project)
        ]);
    }
}
