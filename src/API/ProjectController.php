<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\Project;
use App\Entity\ProjectRate;
use App\Entity\User;
use App\Event\ProjectMetaDefinitionEvent;
use App\Form\API\ProjectApiEditForm;
use App\Form\API\ProjectRateApiForm;
use App\Project\ProjectService;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRateRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectQuery;
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
use Symfony\Component\Validator\Constraints;

#[Route(path: '/projects')]
#[IsGranted('API')]
#[OA\Tag(name: 'Project')]
final class ProjectController extends BaseApiController
{
    public const GROUPS_ENTITY = ['Default', 'Entity', 'Project', 'Project_Entity'];
    public const GROUPS_FORM = ['Default', 'Entity', 'Project'];
    public const GROUPS_COLLECTION = ['Default', 'Collection', 'Project'];
    public const GROUPS_RATE = ['Default', 'Entity', 'Project_Rate'];

    public function __construct(
        private readonly ViewHandlerInterface $viewHandler,
        private readonly ProjectRepository $repository,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly ProjectRateRepository $projectRateRepository,
        private readonly ProjectService $projectService
    ) {
    }

    /**
     * Returns a collection of projects (which are visible to the user)
     */
    #[OA\Response(response: 200, description: 'Returns a collection of projects', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ProjectCollection')))]
    #[Route(methods: ['GET'], path: '', name: 'get_projects')]
    #[Rest\QueryParam(name: 'customer', requirements: '\d+', strict: true, nullable: true, description: 'Customer ID to filter projects')]
    #[Rest\QueryParam(name: 'customers', map: true, requirements: '\d+', strict: true, nullable: true, default: [], description: 'List of customer IDs to filter, e.g.: customers[]=1&customers[]=2')]
    #[Rest\QueryParam(name: 'visible', requirements: '1|2|3', default: 1, strict: true, nullable: true, description: 'Visibility status to filter projects: 1=visible, 2=hidden, 3=both')]
    #[Rest\QueryParam(name: 'start', requirements: [new Constraints\AtLeastOneOf(constraints: [new Constraints\DateTime(format: 'Y-m-d\TH:i:s', message: 'This value is not a valid datetime, expected format: Y-m-d (2022-01-27T20:13:57).'), new Constraints\DateTime(format: 'Y-m-d', message: 'This value is not a valid datetime, expected format: Y-m-d (2022-01-27).')])], strict: true, nullable: true, description: 'Only projects that started before this date will be included. Allowed format: HTML5 (default: now, if end is also empty)')]
    #[Rest\QueryParam(name: 'end', requirements: [new Constraints\AtLeastOneOf(constraints: [new Constraints\DateTime(format: 'Y-m-d\TH:i:s', message: 'This value is not a valid datetime, expected format: Y-m-d (2022-01-27T20:13:57).'), new Constraints\DateTime(format: 'Y-m-d', message: 'This value is not a valid datetime, expected format: Y-m-d (2022-01-27).')])], strict: true, nullable: true, description: 'Only projects that ended after this date will be included. Allowed format: HTML5 (default: now, if start is also empty)')]
    #[Rest\QueryParam(name: 'ignoreDates', requirements: 1, strict: true, nullable: true, description: 'If set, start and end are completely ignored. Allowed values: 1 (default: off)')]
    #[Rest\QueryParam(name: 'globalActivities', requirements: '0|1', strict: true, nullable: true, description: "If given, filters projects by their 'global activity' support. Allowed values: 1 (supports global activities) and 0 (without global activities) (default: all)")]
    #[Rest\QueryParam(name: 'order', requirements: 'ASC|DESC', strict: true, nullable: true, description: 'The result order. Allowed values: ASC, DESC (default: ASC)')]
    #[Rest\QueryParam(name: 'orderBy', requirements: 'id|name|customer', strict: true, nullable: true, description: 'The field by which results will be ordered. Allowed values: id, name, customer (default: name)')]
    #[Rest\QueryParam(name: 'term', description: 'Free search term')]
    public function cgetAction(ParamFetcherInterface $paramFetcher, CustomerRepository $customerRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $query = new ProjectQuery();
        $query->setCurrentUser($user);

        $order = $paramFetcher->get('order');
        if (\is_string($order) && $order !== '') {
            $query->setOrder($order);
        }

        $orderBy = $paramFetcher->get('orderBy');
        if (\is_string($orderBy) && $orderBy !== '') {
            $query->setOrderBy($orderBy);
        }

        /** @var array<int> $customers */
        $customers = $paramFetcher->get('customers');
        $customer = $paramFetcher->get('customer');
        if (\is_string($customer) && $customer !== '') {
            $customers[] = $customer;
        }

        foreach (array_unique($customers) as $customerId) {
            $customer = $customerRepository->find($customerId);
            if ($customer === null) {
                throw $this->createNotFoundException('Unknown customer: ' . $customerId);
            }
            $query->addCustomer($customer);
        }

        $visible = $paramFetcher->get('visible');
        if (\is_string($visible) && $visible !== '') {
            $query->setVisibility((int) $visible);
        }

        $globalActivities = $paramFetcher->get('globalActivities');
        if ($globalActivities !== null) {
            $query->setGlobalActivities((bool) $globalActivities);
        }

        $ignoreDates = false;
        if (null !== ($ign = $paramFetcher->get('ignoreDates'))) {
            $ignoreDates = $ign === 1 || $ign === '1';
        }

        if (!$ignoreDates) {
            $factory = $this->getDateTimeFactory();
            $now = $factory->createDateTime();
            $begin = $paramFetcher->get('start');
            $end = $paramFetcher->get('end');

            if (\is_string($begin) && $begin !== '') {
                $query->setProjectStart($factory->createDateTime($begin));
            }

            if (\is_string($end) && $end !== '') {
                $query->setProjectEnd($factory->createDateTime($end));
            }

            if ($query->getProjectStart() === null && $query->getProjectEnd() === null) {
                $query->setProjectStart($now);
                $query->setProjectEnd($now);
            }
        }

        $term = $paramFetcher->get('term');
        if (\is_string($term) && $term !== '') {
            $query->setSearchTerm(new SearchTerm($term));
        }

        $query->setIsApiCall(true);
        $data = $this->repository->getProjectsForQuery($query);
        $view = new View($data, 200);
        $view->getContext()->setGroups(self::GROUPS_COLLECTION);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns one project
     */
    #[OA\Response(response: 200, description: 'Returns one project entity', content: new OA\JsonContent(ref: '#/components/schemas/ProjectEntity'))]
    #[Route(methods: ['GET'], path: '/{id}', name: 'get_project', requirements: ['id' => '\d+'])]
    #[IsGranted('view', 'project')]
    public function getAction(Project $project): Response
    {
        $view = new View($project, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new project
     */
    #[OA\Post(description: 'Creates a new project and returns it afterwards', responses: [new OA\Response(response: 200, description: 'Returns the new created project', content: new OA\JsonContent(ref: '#/components/schemas/ProjectEntity'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProjectEditForm'))]
    #[Route(methods: ['POST'], path: '', name: 'post_project')]
    public function postAction(Request $request): Response
    {
        if (!$this->isGranted('create_project')) {
            throw $this->createAccessDeniedException('User cannot create projects');
        }

        $project = $this->projectService->createNewProject();

        $form = $this->createForm(ProjectApiEditForm::class, $project, [
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'date_format' => self::DATE_ONLY_FORMAT,
            'include_budget' => $this->isGranted('budget', $project),
            'include_time' => $this->isGranted('time', $project),
        ]);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->projectService->saveNewProject($project);

            $view = new View($project, 200);
            $view->getContext()->setGroups(self::GROUPS_ENTITY);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(self::GROUPS_FORM);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing project
     */
    #[IsGranted('edit', 'project')]
    #[OA\Patch(description: 'Update an existing project, you can pass all or just a subset of all attributes', responses: [new OA\Response(response: 200, description: 'Returns the updated project', content: new OA\JsonContent(ref: '#/components/schemas/ProjectEntity'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProjectEditForm'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Project ID to update', required: true)]
    #[Route(methods: ['PATCH'], path: '/{id}', name: 'patch_project', requirements: ['id' => '\d+'])]
    public function patchAction(Request $request, Project $project): Response
    {
        $event = new ProjectMetaDefinitionEvent($project);
        $this->dispatcher->dispatch($event);

        $form = $this->createForm(ProjectApiEditForm::class, $project, [
            'timezone' => $this->getDateTimeFactory()->getTimezone()->getName(),
            'date_format' => self::DATE_FORMAT,
            'include_budget' => $this->isGranted('budget', $project),
            'include_time' => $this->isGranted('time', $project),
        ]);

        $form->setData($project);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(self::GROUPS_FORM);

            return $this->viewHandler->handle($view);
        }

        $this->projectService->updateProject($project);

        $view = new View($project, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Sets the value of a meta-field for an existing project
     */
    #[IsGranted('edit', 'project')]
    #[OA\Response(response: 200, description: 'Sets the value of an existing/configured meta-field. You cannot create unknown meta-fields, if the given name is not a configured meta-field, this will return an exception.', content: new OA\JsonContent(ref: '#/components/schemas/ProjectEntity'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Project record ID to set the meta-field value for', required: true)]
    #[Route(methods: ['PATCH'], path: '/{id}/meta', requirements: ['id' => '\d+'])]
    #[Rest\RequestParam(name: 'name', strict: true, nullable: false, description: 'The meta-field name')]
    #[Rest\RequestParam(name: 'value', strict: true, nullable: false, description: 'The meta-field value')]
    public function metaAction(Project $project, ParamFetcherInterface $paramFetcher): Response
    {
        $event = new ProjectMetaDefinitionEvent($project);
        $this->dispatcher->dispatch($event);

        $name = $paramFetcher->get('name');
        $value = $paramFetcher->get('value');

        if (null === ($meta = $project->getMetaField($name))) {
            throw $this->createNotFoundException('Unknown meta-field requested');
        }

        $meta->setValue($value);

        $this->projectService->updateProject($project);

        $view = new View($project, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns a collection of all rates for one project
     */
    #[IsGranted('edit', 'project')]
    #[OA\Response(response: 200, description: 'Returns a collection of project rate entities', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ProjectRate')))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The project whose rates will be returned', required: true)]
    #[Route(methods: ['GET'], path: '/{id}/rates', name: 'get_project_rates', requirements: ['id' => '\d+'])]
    public function getRatesAction(Project $project): Response
    {
        $rates = $this->projectRateRepository->getRatesForProject($project);

        $view = new View($rates, 200);
        $view->getContext()->setGroups(self::GROUPS_RATE);

        return $this->viewHandler->handle($view);
    }

    /**
     * Deletes one rate for a project
     */
    #[IsGranted('edit', 'project')]
    #[OA\Delete(responses: [new OA\Response(response: 204, description: 'Returns no content: 204 on successful delete')])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The project whose rate will be removed', required: true)]
    #[OA\Parameter(name: 'rateId', in: 'path', description: 'The rate to remove', required: true)]
    #[Route(methods: ['DELETE'], path: '/{id}/rates/{rateId}', name: 'delete_project_rate', requirements: ['id' => '\d+', 'rateId' => '\d+'])]
    public function deleteRateAction(Project $project, #[MapEntity(mapping: ['rateId' => 'id'])] ProjectRate $rate): Response
    {
        if ($rate->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        $this->projectRateRepository->deleteRate($rate);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }

    /**
     * Adds a new rate to a project
     */
    #[IsGranted('edit', 'project')]
    #[OA\Post(responses: [new OA\Response(response: 200, description: 'Returns the new created rate', content: new OA\JsonContent(ref: '#/components/schemas/ProjectRate'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The project to add the rate for', required: true)]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProjectRateForm'))]
    #[Route(methods: ['POST'], path: '/{id}/rates', name: 'post_project_rate', requirements: ['id' => '\d+'])]
    public function postRateAction(Project $project, Request $request): Response
    {
        $rate = new ProjectRate();
        $rate->setProject($project);

        $form = $this->createForm(ProjectRateApiForm::class, $rate, [
            'method' => 'POST',
        ]);

        $form->setData($rate);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(self::GROUPS_RATE);

            return $this->viewHandler->handle($view);
        }

        $this->projectRateRepository->saveRate($rate);

        $view = new View($rate, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_RATE);

        return $this->viewHandler->handle($view);
    }
}
