<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\ProjectRate;
use App\Entity\User;
use App\Event\ProjectMetaDefinitionEvent;
use App\Form\API\ProjectApiEditForm;
use App\Form\API\ProjectRateApiForm;
use App\Repository\ProjectRateRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectQuery;
use App\Timesheet\UserDateTimeFactory;
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
use Symfony\Component\Validator\Constraints;

/**
 * @RouteResource("Project")
 * @SWG\Tag(name="Project")
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
    /**
     * @var UserDateTimeFactory
     */
    private $dateTime;
    /**
     * @var ProjectRateRepository
     */
    private $projectRateRepository;

    public function __construct(ViewHandlerInterface $viewHandler, ProjectRepository $repository, EventDispatcherInterface $dispatcher, UserDateTimeFactory $dateTime, ProjectRateRepository $projectRateRepository)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
        $this->dateTime = $dateTime;
        $this->projectRateRepository = $projectRateRepository;
    }

    /**
     * Returns a collection of projects.
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
     * @Rest\QueryParam(name="customers", requirements="[\d|,]+", strict=true, nullable=true, description="Comma separated list of customer IDs to filter projects")
     * @Rest\QueryParam(name="visible", requirements="\d+", strict=true, nullable=true, description="Visibility status to filter projects. Allowed values: 1=visible, 2=hidden, 3=both (default: 1)")
     * @Rest\QueryParam(name="start", requirements=@Constraints\DateTime(format="Y-m-d\TH:i:s"), strict=true, nullable=true, description="Only projects that started before this date will be included. Allowed format: HTML5 (default: now, if end is also empty)")
     * @Rest\QueryParam(name="end", requirements=@Constraints\DateTime(format="Y-m-d\TH:i:s"), strict=true, nullable=true, description="Only projects that ended after this date will be included. Allowed format: HTML5 (default: now, if start is also empty)")
     * @Rest\QueryParam(name="ignoreDates", requirements="1", strict=true, nullable=true, description="If set, start and end are completely ignored. Allowed values: 1 (default: off)")
     * @Rest\QueryParam(name="order", requirements="ASC|DESC", strict=true, nullable=true, description="The result order. Allowed values: ASC, DESC (default: ASC)")
     * @Rest\QueryParam(name="orderBy", requirements="id|name|customer", strict=true, nullable=true, description="The field by which results will be ordered. Allowed values: id, name, customer (default: name)")
     * @Rest\QueryParam(name="term", description="Free search term")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $query = new ProjectQuery();
        $query->setCurrentUser($user);

        if (null !== ($order = $paramFetcher->get('order'))) {
            $query->setOrder($order);
        }

        if (null !== ($orderBy = $paramFetcher->get('orderBy'))) {
            $query->setOrderBy($orderBy);
        }

        if (!empty($customers = $paramFetcher->get('customers'))) {
            if (!\is_array($customers)) {
                $customers = explode(',', $customers);
            }
            if (!empty($customers)) {
                $query->setCustomers($customers);
            }
        }

        if (!empty($customer = $paramFetcher->get('customer'))) {
            $query->addCustomer($customer);
        }

        if (null !== ($visible = $paramFetcher->get('visible'))) {
            $query->setVisibility($visible);
        }

        $ignoreDates = false;
        if (null !== $paramFetcher->get('ignoreDates')) {
            $ignoreDates = \intval($paramFetcher->get('ignoreDates')) === 1;
        }

        if (!$ignoreDates) {
            if (null !== ($begin = $paramFetcher->get('start')) && !empty($begin)) {
                $query->setProjectStart($this->dateTime->createDateTime($begin));
            }

            if (null !== ($end = $paramFetcher->get('end')) && !empty($end)) {
                $query->setProjectEnd($this->dateTime->createDateTime($end));
            }

            if (empty($begin) && empty($end)) {
                $now = $this->dateTime->createDateTime();
                $query->setProjectStart($now);
                $query->setProjectEnd($now);
            }
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

        $form = $this->createForm(ProjectApiEditForm::class, $project, [
            'date_format' => self::DATE_FORMAT,
            'include_budget' => $this->isGranted('budget', $project),
        ]);

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

        $form = $this->createForm(ProjectApiEditForm::class, $project, [
            'date_format' => self::DATE_FORMAT,
            'include_budget' => $this->isGranted('budget', $project),
        ]);

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
     * Sets the value of a meta-field for an existing project
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

    /**
     * Returns a collection of all rates for one project
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns a collection of project rate entities",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/ProjectRate")
     *      )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The project whose rates will be returned",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function getRatesAction(int $id): Response
    {
        /** @var Project|null $project */
        $project = $this->repository->find($id);

        if (null === $project) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $project)) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        $rates = $this->projectRateRepository->getRatesForProject($project);

        $view = new View($rates, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'ProjectRate']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Deletes one rate for an project
     *
     * @SWG\Delete(
     *      @SWG\Response(
     *          response=204,
     *          description="Returns no content: 204 on successful delete"
     *      )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The project whose rate will be removed",
     *      required=true,
     * )
     * @SWG\Parameter(
     *      name="rateId",
     *      in="path",
     *      type="integer",
     *      description="The rate to remove",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function deleteRateAction(string $id, string $rateId): Response
    {
        /** @var Project|null $project */
        $project = $this->repository->find($id);

        if (null === $project) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $project)) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        /** @var ProjectRate|null $rate */
        $rate = $this->projectRateRepository->find($rateId);

        if (null === $rate || $rate->getProject() !== $project) {
            throw new NotFoundException();
        }

        $this->projectRateRepository->deleteRate($rate);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }

    /**
     * Adds a new rate to an project
     *
     * @SWG\Post(
     *  @SWG\Response(
     *      response=200,
     *      description="Returns the new created rate",
     *      @SWG\Schema(ref="#/definitions/ProjectRate")
     *  )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The project to add the rate for",
     *      required=true,
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/ProjectRateForm")
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function postRateAction(int $id, Request $request): Response
    {
        /** @var Project|null $project */
        $project = $this->repository->find($id);

        if (null === $project) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $project)) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        $rate = new ProjectRate();
        $rate->setProject($project);

        $form = $this->createForm(ProjectRateApiForm::class, $rate, [
            'method' => 'POST',
        ]);

        $form->setData($rate);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(['Default', 'Entity', 'ProjectRate']);

            return $this->viewHandler->handle($view);
        }

        $this->projectRateRepository->saveRate($rate);

        $view = new View($rate, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'ProjectRate']);

        return $this->viewHandler->handle($view);
    }
}
