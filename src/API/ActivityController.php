<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\Activity;
use App\Entity\ActivityRate;
use App\Entity\User;
use App\Event\ActivityMetaDefinitionEvent;
use App\Form\API\ActivityApiEditForm;
use App\Form\API\ActivityRateApiForm;
use App\Repository\ActivityRateRepository;
use App\Repository\ActivityRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\ActivityQuery;
use App\Utils\SearchTerm;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use OpenApi\Attributes as OA;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/activities')]
#[IsGranted('API')]
#[OA\Tag(name: 'Activity')]
final class ActivityController extends BaseApiController
{
    public const GROUPS_ENTITY = ['Default', 'Entity', 'Activity', 'Activity_Entity'];
    public const GROUPS_FORM = ['Default', 'Entity', 'Activity'];
    public const GROUPS_COLLECTION = ['Default', 'Collection', 'Activity'];
    public const GROUPS_RATE = ['Default', 'Entity', 'Activity_Rate'];

    public function __construct(
        private readonly ViewHandlerInterface $viewHandler,
        private readonly ActivityRepository $repository,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly ActivityRateRepository $activityRateRepository
    ) {
    }

    /**
     * Returns a collection of activities (which are visible to the user)
     */
    #[OA\Response(response: 200, description: 'Returns a collection of activities', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ActivityCollection')))]
    #[Route(methods: ['GET'], path: '', name: 'get_activities')]
    #[Rest\QueryParam(name: 'project', requirements: '\d+', strict: true, nullable: true, description: 'Project ID to filter activities')]
    #[Rest\QueryParam(name: 'projects', map: true, requirements: '\d+', strict: true, nullable: true, default: [], description: 'List of project IDs to filter activities, e.g.: projects[]=1&projects[]=2')]
    #[Rest\QueryParam(name: 'visible', requirements: '1|2|3', default: 1, strict: true, nullable: true, description: 'Visibility status to filter activities: 1=visible, 2=hidden, 3=all')]
    #[Rest\QueryParam(name: 'globals', strict: true, nullable: true, description: 'Use if you want to fetch only global activities. Allowed values: true (default: false)')]
    #[Rest\QueryParam(name: 'orderBy', requirements: 'id|name|project', strict: true, nullable: true, description: 'The field by which results will be ordered. Allowed values: id, name, project (default: name)')]
    #[Rest\QueryParam(name: 'order', requirements: 'ASC|DESC', strict: true, nullable: true, description: 'The result order. Allowed values: ASC, DESC (default: ASC)')]
    #[Rest\QueryParam(name: 'term', description: 'Free search term')]
    public function cgetAction(ParamFetcherInterface $paramFetcher, ProjectRepository $projectRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $query = new ActivityQuery();
        $query->setCurrentUser($user);

        $order = $paramFetcher->get('order');
        if (\is_string($order) && $order !== '') {
            $query->setOrder($order);
        }

        $orderBy = $paramFetcher->get('orderBy');
        if (\is_string($orderBy) && $orderBy !== '') {
            $query->setOrderBy($orderBy);
        }

        if (null !== $paramFetcher->get('globals')) {
            $query->setGlobalsOnly(true);
        }

        /** @var array<int> $projects */
        $projects = $paramFetcher->get('projects');
        $project = $paramFetcher->get('project');
        if (\is_string($project) && $project !== '') {
            $projects[] = $project;
        }

        foreach (array_unique($projects) as $projectId) {
            $project = $projectRepository->find($projectId);
            if ($project === null) {
                throw $this->createNotFoundException('Unknown project: ' . $projectId);
            }
            $query->addProject($project);
        }

        $visible = $paramFetcher->get('visible');
        if (\is_string($visible) && $visible !== '') {
            $query->setVisibility((int) $visible);
        }

        $term = $paramFetcher->get('term');
        if (\is_string($term) && $term !== '') {
            $query->setSearchTerm(new SearchTerm($term));
        }

        $query->setIsApiCall(true);
        $data = $this->repository->getActivitiesForQuery($query);
        $view = new View($data, 200);
        $view->getContext()->setGroups(self::GROUPS_COLLECTION);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns one activity
     */
    #[OA\Response(response: 200, description: 'Returns one activity entity', content: new OA\JsonContent(ref: '#/components/schemas/ActivityEntity'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Activity ID to fetch', required: true)]
    #[Route(methods: ['GET'], path: '/{id}', name: 'get_activity', requirements: ['id' => '\d+'])]
    #[IsGranted('view', 'activity')]
    public function getAction(Activity $activity): Response
    {
        $view = new View($activity, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new activity
     */
    #[OA\Post(description: 'Creates a new activity and returns it afterwards', responses: [new OA\Response(response: 200, description: 'Returns the new created activity', content: new OA\JsonContent(ref: '#/components/schemas/ActivityEntity'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ActivityEditForm'))]
    #[Route(methods: ['POST'], path: '', name: 'post_activity')]
    public function postAction(Request $request): Response
    {
        if (!$this->isGranted('create_activity')) {
            throw $this->createAccessDeniedException('User cannot create activities');
        }

        $activity = new Activity();

        $event = new ActivityMetaDefinitionEvent($activity);
        $this->dispatcher->dispatch($event);

        $form = $this->createForm(ActivityApiEditForm::class, $activity, [
            'include_budget' => $this->isGranted('budget', $activity),
            'include_time' => $this->isGranted('time', $activity),
        ]);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->repository->saveActivity($activity);

            $view = new View($activity, 200);
            $view->getContext()->setGroups(self::GROUPS_ENTITY);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(self::GROUPS_FORM);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing activity
     */
    #[IsGranted('edit', 'activity')]
    #[OA\Patch(description: 'Update an existing activity, you can pass all or just a subset of all attributes', responses: [new OA\Response(response: 200, description: 'Returns the updated activity', content: new OA\JsonContent(ref: '#/components/schemas/ActivityEntity'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ActivityEditForm'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Activity ID to update', required: true)]
    #[Route(methods: ['PATCH'], path: '/{id}', name: 'patch_activity', requirements: ['id' => '\d+'])]
    public function patchAction(Request $request, Activity $activity): Response
    {
        $event = new ActivityMetaDefinitionEvent($activity);
        $this->dispatcher->dispatch($event);

        $form = $this->createForm(ActivityApiEditForm::class, $activity, [
            'include_budget' => $this->isGranted('budget', $activity),
            'include_time' => $this->isGranted('time', $activity),
        ]);

        $form->setData($activity);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(self::GROUPS_FORM);

            return $this->viewHandler->handle($view);
        }

        $this->repository->saveActivity($activity);

        $view = new View($activity, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Sets the value of a meta-field for an existing activity
     */
    #[IsGranted('edit', 'activity')]
    #[OA\Response(response: 200, description: 'Sets the value of an existing/configured meta-field. You cannot create unknown meta-fields, if the given name is not a configured meta-field, this will return an exception.', content: new OA\JsonContent(ref: '#/components/schemas/ActivityEntity'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Activity record ID to set the meta-field value for', required: true)]
    #[Route(methods: ['PATCH'], path: '/{id}/meta', requirements: ['id' => '\d+'])]
    #[Rest\RequestParam(name: 'name', strict: true, nullable: false, description: 'The meta-field name')]
    #[Rest\RequestParam(name: 'value', strict: true, nullable: false, description: 'The meta-field value')]
    public function metaAction(Activity $activity, ParamFetcherInterface $paramFetcher): Response
    {
        $event = new ActivityMetaDefinitionEvent($activity);
        $this->dispatcher->dispatch($event);

        $name = $paramFetcher->get('name');
        $value = $paramFetcher->get('value');

        if (null === ($meta = $activity->getMetaField($name))) {
            throw $this->createNotFoundException('Unknown meta-field requested');
        }

        $meta->setValue($value);

        $this->repository->saveActivity($activity);

        $view = new View($activity, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns a collection of all rates for one activity
     */
    #[IsGranted('edit', 'activity')]
    #[OA\Response(response: 200, description: 'Returns a collection of activity rate entities', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ActivityRate')))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The activity whose rates will be returned', required: true)]
    #[Route(methods: ['GET'], path: '/{id}/rates', name: 'get_activity_rates', requirements: ['id' => '\d+'])]
    public function getRatesAction(Activity $activity): Response
    {
        $rates = $this->activityRateRepository->getRatesForActivity($activity);

        $view = new View($rates, 200);
        $view->getContext()->setGroups(self::GROUPS_RATE);

        return $this->viewHandler->handle($view);
    }

    /**
     * Deletes one rate for an activity
     */
    #[IsGranted('edit', 'activity')]
    #[OA\Delete(responses: [new OA\Response(response: 204, description: 'Returns no content: 204 on successful delete')])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The activity whose rate will be removed', required: true)]
    #[OA\Parameter(name: 'rateId', in: 'path', description: 'The rate to remove', required: true)]
    #[Route(methods: ['DELETE'], path: '/{id}/rates/{rateId}', name: 'delete_activity_rate', requirements: ['id' => '\d+', 'rateId' => '\d+'])]
    public function deleteRateAction(Activity $activity, #[MapEntity(mapping: ['rateId' => 'id'])] ActivityRate $rate): Response
    {
        if ($rate->getActivity() !== $activity) {
            throw $this->createNotFoundException();
        }

        $this->activityRateRepository->deleteRate($rate);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }

    /**
     * Adds a new rate to an activity
     */
    #[IsGranted('edit', 'activity')]
    #[OA\Post(responses: [new OA\Response(response: 200, description: 'Returns the new created rate', content: new OA\JsonContent(ref: '#/components/schemas/ActivityRate'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The activity to add the rate for', required: true)]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ActivityRateForm'))]
    #[Route(methods: ['POST'], path: '/{id}/rates', name: 'post_activity_rate', requirements: ['id' => '\d+'])]
    public function postRateAction(Activity $activity, Request $request): Response
    {
        $rate = new ActivityRate();
        $rate->setActivity($activity);

        $form = $this->createForm(ActivityRateApiForm::class, $rate, [
            'method' => 'POST',
        ]);

        $form->setData($rate);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(self::GROUPS_RATE);

            return $this->viewHandler->handle($view);
        }

        $this->activityRateRepository->saveRate($rate);

        $view = new View($rate, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_RATE);

        return $this->viewHandler->handle($view);
    }
}
