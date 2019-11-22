<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\Project;
use App\Event\ProjectMetaDefinitionEvent;
use App\Form\API\ProjectApiEditForm;
use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectQuery;
use App\Utils\SearchTerm;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @RouteResource("Project")
 *
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
class ProjectController extends BaseApiController
{
    /**
     * @var ProjectRepository
     */
    private $repository;
    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(ViewHandlerInterface $viewHandler, ProjectRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Returns a collection of projects
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns a collection of project entities",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/ProjectCollection")
     *      )
     * )
     * @Rest\QueryParam(name="customer", requirements="\d+", strict=true, nullable=true, description="Customer ID to filter projects")
     * @Rest\QueryParam(name="visible", requirements="\d+", strict=true, nullable=true, description="Visibility status to filter projects (1=visible, 2=hidden, 3=both)")
     * @Rest\QueryParam(name="order", requirements="ASC|DESC", strict=true, nullable=true, description="The result order. Allowed values: ASC, DESC (default: ASC)")
     * @Rest\QueryParam(name="orderBy", requirements="id|name|customer", strict=true, nullable=true, description="The field by which results will be ordered. Allowed values: id, name, customer (default: name)")
     * @Rest\QueryParam(name="term", requirements="[a-zA-Z0-9 \-,:]+", strict=true, nullable=true, description="Free search term")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher): Response
    {
        $query = new ProjectQuery();
        $query->setCurrentUser($this->getUser());

        if (null !== ($order = $paramFetcher->get('order'))) {
            $query->setOrder($order);
        }

        if (null !== ($orderBy = $paramFetcher->get('orderBy'))) {
            $query->setOrderBy($orderBy);
        }

        if (!empty($customer = $paramFetcher->get('customer'))) {
            $query->setCustomer($customer);
        }

        if (null !== ($visible = $paramFetcher->get('visible'))) {
            $query->setVisibility($visible);
        }

        if (!empty($term = $paramFetcher->get('term'))) {
            $query->setSearchTerm(new SearchTerm($term));
        }

        $data = $this->repository->getProjectsForQuery($query);
        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection', 'Project']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns one project
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns one project entity",
     *      @SWG\Schema(ref="#/definitions/ProjectEntity"),
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function getAction(int $id): Response
    {
        $data = $this->repository->find($id);

        if (null === $data) {
            throw new NotFoundException();
        }

        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Project']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new project
     *
     * @SWG\Post(
     *      description="Creates a new project and returns it afterwards",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the new created project",
     *          @SWG\Schema(ref="#/definitions/ProjectEntity"),
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/ProjectEditForm")
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function postAction(Request $request): Response
    {
        if (!$this->isGranted('create_project')) {
            throw new AccessDeniedHttpException('User cannot create projects');
        }

        $project = new Project();

        $event = new ProjectMetaDefinitionEvent($project);
        $this->dispatcher->dispatch($event);

        $form = $this->createForm(ProjectApiEditForm::class, $project);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->repository->saveProject($project);

            $view = new View($project, 200);
            $view->getContext()->setGroups(['Default', 'Entity', 'Project']);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(['Default', 'Entity', 'Project']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing project
     *
     * @SWG\Patch(
     *      description="Update an existing project, you can pass all or just a subset of all attributes",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the updated project",
     *          @SWG\Schema(ref="#/definitions/ProjectEntity")
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/ProjectEditForm")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Project ID to update",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function patchAction(Request $request, int $id): Response
    {
        $project = $this->repository->find($id);

        if (null === $project) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $project)) {
            throw new AccessDeniedHttpException('User cannot update project');
        }

        $event = new ProjectMetaDefinitionEvent($project);
        $this->dispatcher->dispatch($event);

        $form = $this->createForm(ProjectApiEditForm::class, $project);

        $form->setData($project);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(['Default', 'Entity', 'Project']);

            return $this->viewHandler->handle($view);
        }

        $this->repository->saveProject($project);

        $view = new View($project, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Project']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Sets the value of a meta-field for an existing project.
     *
     * @SWG\Response(
     *      response=200,
     *      description="Sets the value of an existing/configured meta-field. You cannot create unknown meta-fields, if the given name is not a configured meta-field, this will return an exception.",
     *      @SWG\Schema(ref="#/definitions/ProjectEntity")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Project record ID to set the meta-field value for",
     *      required=true,
     * )
     * @Rest\RequestParam(name="name", strict=true, nullable=false, description="The meta-field name")
     * @Rest\RequestParam(name="value", strict=true, nullable=false, description="The meta-field value")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function metaAction(int $id, ParamFetcherInterface $paramFetcher): Response
    {
        $project = $this->repository->find($id);

        if (null === $project) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $project)) {
            throw new AccessDeniedHttpException('You are not allowed to update this project');
        }

        $event = new ProjectMetaDefinitionEvent($project);
        $this->dispatcher->dispatch($event);

        $name = $paramFetcher->get('name');
        $value = $paramFetcher->get('value');

        if (null === ($meta = $project->getMetaField($name))) {
            throw new \InvalidArgumentException('Unknown meta-field requested');
        }

        $meta->setValue($value);

        $this->repository->saveProject($project);

        $view = new View($project, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Project']);

        return $this->viewHandler->handle($view);
    }
}
