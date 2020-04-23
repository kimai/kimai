<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\Activity;
use App\Entity\ActivityRate;
use App\Event\ActivityMetaDefinitionEvent;
use App\Form\API\ActivityApiEditForm;
use App\Form\API\ActivityRateApiForm;
use App\Repository\ActivityRateRepository;
use App\Repository\ActivityRepository;
use App\Repository\Query\ActivityQuery;
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
 * @RouteResource("Activity")
 * @SWG\Tag(name="Activity")
 *
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
class ActivityController extends BaseApiController
{
    /**
     * @var ActivityRepository
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
     * @var ActivityRateRepository
     */
    private $activityRateRepository;

    public function __construct(ViewHandlerInterface $viewHandler, ActivityRepository $repository, EventDispatcherInterface $dispatcher, ActivityRateRepository $activityRateRepository)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
        $this->activityRateRepository = $activityRateRepository;
    }

    /**
     * Returns a collection of activities
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns a collection of activity entities",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/ActivityCollection")
     *      )
     * )
     * @Rest\QueryParam(name="project", requirements="\d+", strict=true, nullable=true, description="Project ID to filter activities")
     * @Rest\QueryParam(name="projects", requirements="[\d|,]+", strict=true, nullable=true, description="Comma separated list of project IDs to filter activities")
     * @Rest\QueryParam(name="visible", requirements="1|2|3", strict=true, nullable=true, description="Visibility status to filter activities. Allowed values: 1=visible, 2=hidden, 3=all (default: 1)")
     * @Rest\QueryParam(name="globals", requirements="true", strict=true, nullable=true, description="Use if you want to fetch only global activities. Allowed values: true (default: false)")
     * @Rest\QueryParam(name="globalsFirst", requirements="true|false", strict=true, nullable=true, description="Deprecated parameter, value is not used any more")
     * @Rest\QueryParam(name="orderBy", requirements="id|name|project", strict=true, nullable=true, description="The field by which results will be ordered. Allowed values: id, name, project (default: name)")
     * @Rest\QueryParam(name="order", requirements="ASC|DESC", strict=true, nullable=true, description="The result order. Allowed values: ASC, DESC (default: ASC)")
     * @Rest\QueryParam(name="term", description="Free search term")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher): Response
    {
        $query = new ActivityQuery();

        if (null !== ($order = $paramFetcher->get('order'))) {
            $query->setOrder($order);
        }

        if (null !== ($orderBy = $paramFetcher->get('orderBy'))) {
            $query->setOrderBy($orderBy);
        }

        if (null !== $paramFetcher->get('globals')) {
            $query->setGlobalsOnly(true);
        }

        if (null !== $paramFetcher->get('globalsFirst')) {
            @trigger_error('API parameter globalsFirst is deprecated and will be removed with 2.0', E_USER_DEPRECATED);
        }

        if (!empty($projects = $paramFetcher->get('projects'))) {
            if (!\is_array($projects)) {
                $projects = explode(',', $projects);
            }
            if (!empty($projects)) {
                $query->setProjects($projects);
            }
        }

        if (!empty($project = $paramFetcher->get('project'))) {
            $query->addProject($project);
        }

        if (null !== ($visible = $paramFetcher->get('visible'))) {
            $query->setVisibility($visible);
        }

        if (!empty($term = $paramFetcher->get('term'))) {
            $query->setSearchTerm(new SearchTerm($term));
        }

        $data = $this->repository->getActivitiesForQuery($query);
        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection', 'Activity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns one activity
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns one activity entity",
     *      @SWG\Schema(ref="#/definitions/ActivityEntity"),
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Activity ID to fetch",
     *      required=true,
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
        $view->getContext()->setGroups(['Default', 'Entity', 'Activity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new activity
     *
     * @SWG\Post(
     *      description="Creates a new activity and returns it afterwards",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the new created activity",
     *          @SWG\Schema(ref="#/definitions/ActivityEntity"),
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/ActivityEditForm")
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function postAction(Request $request): Response
    {
        if (!$this->isGranted('create_activity')) {
            throw new AccessDeniedHttpException('User cannot create activities');
        }

        $activity = new Activity();

        $event = new ActivityMetaDefinitionEvent($activity);
        $this->dispatcher->dispatch($event);

        $form = $this->createForm(ActivityApiEditForm::class, $activity, [
            'include_budget' => $this->isGranted('budget', $activity),
        ]);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->repository->saveActivity($activity);

            $view = new View($activity, 200);
            $view->getContext()->setGroups(['Default', 'Entity', 'Activity']);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(['Default', 'Entity', 'Activity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing activity
     *
     * @SWG\Patch(
     *      description="Update an existing activity, you can pass all or just a subset of all attributes",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the updated activity",
     *          @SWG\Schema(ref="#/definitions/ActivityEntity")
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/ActivityEditForm")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Activity ID to update",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function patchAction(Request $request, int $id): Response
    {
        $activity = $this->repository->find($id);

        if (null === $activity) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $activity)) {
            throw new AccessDeniedHttpException('User cannot update activity');
        }

        $event = new ActivityMetaDefinitionEvent($activity);
        $this->dispatcher->dispatch($event);

        $form = $this->createForm(ActivityApiEditForm::class, $activity, [
            'include_budget' => $this->isGranted('budget', $activity),
        ]);

        $form->setData($activity);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(['Default', 'Entity', 'Activity']);

            return $this->viewHandler->handle($view);
        }

        $this->repository->saveActivity($activity);

        $view = new View($activity, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Activity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Sets the value of a meta-field for an existing activity
     *
     * @SWG\Response(
     *      response=200,
     *      description="Sets the value of an existing/configured meta-field. You cannot create unknown meta-fields, if the given name is not a configured meta-field, this will return an exception.",
     *      @SWG\Schema(ref="#/definitions/ActivityEntity")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Activity record ID to set the meta-field value for",
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
        $activity = $this->repository->find($id);

        if (null === $activity) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $activity)) {
            throw new AccessDeniedHttpException('You are not allowed to update this activity');
        }

        $event = new ActivityMetaDefinitionEvent($activity);
        $this->dispatcher->dispatch($event);

        $name = $paramFetcher->get('name');
        $value = $paramFetcher->get('value');

        if (null === ($meta = $activity->getMetaField($name))) {
            throw new \InvalidArgumentException('Unknown meta-field requested');
        }

        $meta->setValue($value);

        $this->repository->saveActivity($activity);

        $view = new View($activity, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Project']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns a collection of all rates for one activity
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns a collection of activity rate entities",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/ActivityRate")
     *      )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The activity whose rates will be returned",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function getRatesAction(int $id): Response
    {
        /** @var Activity|null $activity */
        $activity = $this->repository->find($id);

        if (null === $activity) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $activity)) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        $rates = $this->activityRateRepository->getRatesForActivity($activity);

        $view = new View($rates, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'ActivityRate']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Deletes one rate for an activity
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
     *      description="The activity whose rate will be removed",
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
        /** @var Activity|null $activity */
        $activity = $this->repository->find($id);

        if (null === $activity) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $activity)) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        /** @var ActivityRate|null $rate */
        $rate = $this->activityRateRepository->find($rateId);

        if (null === $rate || $rate->getActivity() !== $activity) {
            throw new NotFoundException();
        }

        $this->activityRateRepository->deleteRate($rate);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }

    /**
     * Adds a new rate to an activity
     *
     * @SWG\Post(
     *  @SWG\Response(
     *      response=200,
     *      description="Returns the new created rate",
     *      @SWG\Schema(ref="#/definitions/ActivityRate")
     *  )
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="The activity to add the rate for",
     *      required=true,
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/ActivityRateForm")
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function postRateAction(int $id, Request $request): Response
    {
        /** @var Activity|null $activity */
        $activity = $this->repository->find($id);

        if (null === $activity) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $activity)) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        $rate = new ActivityRate();
        $rate->setActivity($activity);

        $form = $this->createForm(ActivityRateApiForm::class, $rate, [
            'method' => 'POST',
        ]);

        $form->setData($rate);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(['Default', 'Entity', 'ActivityRate']);

            return $this->viewHandler->handle($view);
        }

        $this->activityRateRepository->saveRate($rate);

        $view = new View($rate, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'ActivityRate']);

        return $this->viewHandler->handle($view);
    }
}
