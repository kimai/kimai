<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Event\RecentActivityEvent;
use App\Event\TimesheetDuplicatePostEvent;
use App\Event\TimesheetDuplicatePreEvent;
use App\Event\TimesheetMetaDefinitionEvent;
use App\Form\API\TimesheetApiEditForm;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TagRepository;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use App\Timesheet\TimesheetService;
use App\Timesheet\TrackingMode\TrackingModeInterface;
use App\Utils\SearchTerm;
use App\Validator\ValidationFailedException;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use OpenApi\Attributes as OA;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(path: '/timesheets')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
#[OA\Tag(name: 'Timesheet')]
final class TimesheetController extends BaseApiController
{
    public const GROUPS_ENTITY = ['Default', 'Entity', 'Timesheet', 'Timesheet_Entity', 'Not_Expanded'];
    public const GROUPS_ENTITY_FULL = ['Default', 'Entity', 'Timesheet', 'Timesheet_Entity', 'Expanded'];
    public const GROUPS_FORM = ['Default', 'Entity', 'Timesheet', 'Not_Expanded'];
    public const GROUPS_COLLECTION = ['Default', 'Collection', 'Timesheet', 'Not_Expanded'];
    public const GROUPS_COLLECTION_FULL = ['Default', 'Collection', 'Timesheet', 'Expanded'];

    public function __construct(
        private ViewHandlerInterface $viewHandler,
        private TimesheetRepository $repository,
        private TagRepository $tagRepository,
        private EventDispatcherInterface $dispatcher,
        private TimesheetService $service
    ) {
    }

    protected function getTrackingMode(): TrackingModeInterface
    {
        return $this->service->getActiveTrackingMode();
    }

    /**
     * Returns a collection of timesheet records (which are visible to the user)
     */
    #[IsGranted(new Expression("is_granted('view_own_timesheet') or is_granted('view_other_timesheet')"))]
    #[OA\Response(response: 200, description: 'Returns a collection of timesheet records. The datetime fields are given in the users local time including the timezone offset (ISO-8601).', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/TimesheetCollection')))]
    #[Rest\Get(path: '', name: 'get_timesheets')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\QueryParam(name: 'user', requirements: '\d+|all', strict: true, nullable: true, description: "User ID to filter timesheets. Needs permission 'view_other_timesheet', pass 'all' to fetch data for all user (default: current user)")]
    #[Rest\QueryParam(name: 'customer', requirements: '\d+', strict: true, nullable: true, description: 'Customer ID to filter timesheets')]
    #[Rest\QueryParam(name: 'customers', map: true, requirements: '\d+', strict: true, nullable: true, default: [], description: 'List of customer IDs to filter, e.g.: customers[]=1&customers[]=2')]
    #[Rest\QueryParam(name: 'project', requirements: '\d+', strict: true, nullable: true, description: 'Project ID to filter timesheets')]
    #[Rest\QueryParam(name: 'projects', map: true, requirements: '\d+', strict: true, nullable: true, default: [], description: 'List of project IDs to filter, e.g.: projects[]=1&projects[]=2')]
    #[Rest\QueryParam(name: 'activity', requirements: '\d+', strict: true, nullable: true, description: 'Activity ID to filter timesheets')]
    #[Rest\QueryParam(name: 'activities', map: true, requirements: '\d+', strict: true, nullable: true, default: [], description: 'List of activity IDs to filter, e.g.: activities[]=1&activities[]=2')]
    #[Rest\QueryParam(name: 'page', requirements: '\d+', strict: true, nullable: true, description: 'The page to display, renders a 404 if not found (default: 1)')]
    #[Rest\QueryParam(name: 'size', requirements: '\d+', strict: true, nullable: true, description: 'The amount of entries for each page (default: 50)')]
    #[Rest\QueryParam(name: 'tags', map: true, strict: true, nullable: true, default: [], description: 'List of tag names, e.g. tags[]=bar&tags[]=foo')]
    #[Rest\QueryParam(name: 'orderBy', requirements: 'id|begin|end|rate', strict: true, nullable: true, description: 'The field by which results will be ordered. Allowed values: id, begin, end, rate (default: begin)')]
    #[Rest\QueryParam(name: 'order', requirements: 'ASC|DESC', strict: true, nullable: true, description: 'The result order. Allowed values: ASC, DESC (default: DESC)')]
    #[Rest\QueryParam(name: 'begin', requirements: [new Constraints\DateTime(format: 'Y-m-d\TH:i:s')], strict: true, nullable: true, description: 'Only records after this date will be included (format: HTML5)')]
    #[Rest\QueryParam(name: 'end', requirements: [new Constraints\DateTime(format: 'Y-m-d\TH:i:s')], strict: true, nullable: true, description: 'Only records before this date will be included (format: HTML5)')]
    #[Rest\QueryParam(name: 'exported', requirements: '0|1', strict: true, nullable: true, description: 'Use this flag if you want to filter for export state. Allowed values: 0=not exported, 1=exported (default: all)')]
    #[Rest\QueryParam(name: 'active', requirements: '0|1', strict: true, nullable: true, description: 'Filter for running/active records. Allowed values: 0=stopped, 1=active (default: all)')]
    #[Rest\QueryParam(name: 'billable', requirements: '0|1', strict: true, nullable: true, description: 'Filter for non-/billable records. Allowed values: 0=non-billable, 1=billable (default: all)')]
    #[Rest\QueryParam(name: 'full', strict: true, nullable: true, description: 'Allows to fetch fully serialized objects including subresources. Allowed values: true (default: false)')]
    #[Rest\QueryParam(name: 'term', description: 'Free search term')]
    #[Rest\QueryParam(name: 'modified_after', requirements: [new Constraints\DateTime(format: 'Y-m-d\TH:i:s')], strict: true, nullable: true, description: 'Only records changed after this date will be included (format: HTML5). Available since Kimai 1.10 and works only for records that were created/updated since then.')]
    public function cgetAction(ParamFetcherInterface $paramFetcher, CustomerRepository $customerRepository, ProjectRepository $projectRepository, ActivityRepository $activityRepository, UserRepository $userRepository): Response
    {
        $query = new TimesheetQuery(false);
        $query->setUser($this->getUser());

        if ($this->isGranted('view_other_timesheet')) {
            $userId = $paramFetcher->get('user');
            if (\is_string($userId) && $userId !== '') {
                if ('all' === $userId) {
                    $query->setUser(null);
                } else {
                    $user = $userRepository->find($userId);
                    if ($user === null) {
                        throw $this->createNotFoundException('Unknown user: ' . $userId);
                    }
                    $query->setUser($user);
                }
            }
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

        /** @var array<int> $projects */
        $projects = $paramFetcher->get('projects');
        $project = $paramFetcher->get('project');
        if (\is_string($project) && $project !== '') {
            $projects[] = $project;
        }

        foreach (array_unique($projects) as $projectId) {
            $project = $projectRepository->find($projectId);
            if ($project === null) {
                throw $this->createNotFoundException('Unknown project: ' . $project);
            }
            $query->addProject($project);
        }

        /** @var array<int> $activities */
        $activities = $paramFetcher->get('activities');
        $activity = $paramFetcher->get('activity');
        if (\is_string($activity) && $activity !== '') {
            $activities[] = $activity;
        }

        foreach (array_unique($activities) as $activityId) {
            $activity = $activityRepository->find($activityId);
            if ($activity === null) {
                throw $this->createNotFoundException('Unknown activity: ' . $activity);
            }
            $query->addActivity($activity);
        }

        $page = $paramFetcher->get('page');
        if (\is_string($page) && $page !== '') {
            $query->setPage((int) $page);
        }

        $size = $paramFetcher->get('size');
        if (\is_string($size) && $size !== '') {
            $query->setPageSize((int) $size);
        }

        $tags = $paramFetcher->get('tags');
        if (\is_array($tags) && \count($tags) > 0) {
            $tags = $this->tagRepository->findTagsByName($tags);
            foreach ($tags as $tag) {
                $query->addTag($tag);
            }
        }

        $order = $paramFetcher->get('order');
        if (\is_string($order) && $order !== '') {
            $query->setOrder($order);
        }

        $orderBy = $paramFetcher->get('orderBy');
        if (\is_string($orderBy) && $orderBy !== '') {
            $query->setOrderBy($orderBy);
        }

        $factory = $this->getDateTimeFactory();

        $begin = $paramFetcher->get('begin');
        if (\is_string($begin) && $begin !== '') {
            $query->setBegin($factory->createDateTime($begin));
        }

        $end = $paramFetcher->get('end');
        if (\is_string($end) && $end !== '') {
            $query->setEnd($factory->createDateTime($end));
        }

        $active = $paramFetcher->get('active');
        if (\is_string($active) && $active !== '') {
            $active = (int) $active;
            if ($active === 1) {
                $query->setState(TimesheetQuery::STATE_RUNNING);
            } elseif ($active === 0) {
                $query->setState(TimesheetQuery::STATE_STOPPED);
            }
        }

        $billable = $paramFetcher->get('billable');
        if (\is_string($billable) && $billable !== '') {
            $billable = (int) $billable;
            if ($billable === 1) {
                $query->setBillable(true);
            } elseif ($billable === 0) {
                $query->setBillable(false);
            }
        }

        $exported = $paramFetcher->get('exported');
        if (\is_string($exported) && $exported !== '') {
            $exported = (int) $exported;
            if ($exported === 1) {
                $query->setExported(TimesheetQuery::STATE_EXPORTED);
            } elseif ($exported === 0) {
                $query->setExported(TimesheetQuery::STATE_NOT_EXPORTED);
            }
        }

        $term = $paramFetcher->get('term');
        if (\is_string($term) && $term !== '') {
            $query->setSearchTerm(new SearchTerm($term));
        }

        if (!empty($modifiedAfter = $paramFetcher->get('modified_after'))) {
            $query->setModifiedAfter($factory->createDateTime($modifiedAfter));
        }

        $data = $this->repository->getPagerfantaForQuery($query);
        $results = (array) $data->getCurrentPageResults();

        $view = new View($results, 200);
        $this->addPagination($view, $data);

        if (null !== $paramFetcher->get('full')) {
            $view->getContext()->setGroups(self::GROUPS_COLLECTION_FULL);
        } else {
            $view->getContext()->setGroups(self::GROUPS_COLLECTION);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns one timesheet record
     */
    #[IsGranted('view', 'timesheet')]
    #[OA\Response(response: 200, description: 'Returns one timesheet record. Be aware that the datetime fields are given in the users local time including the timezone offset via ISO 8601.', content: new OA\JsonContent(ref: '#/components/schemas/TimesheetEntity'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Timesheet record ID to fetch', required: true)]
    #[Rest\Get(path: '/{id}', name: 'get_timesheet', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function getAction(Timesheet $timesheet): Response
    {
        $view = new View($timesheet, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new timesheet record
     */
    #[IsGranted('create_own_timesheet')]
    #[OA\Post(description: 'Creates a new timesheet record for the current user and returns it afterwards.', responses: [new OA\Response(response: 200, description: 'Returns the new created timesheet', content: new OA\JsonContent(ref: '#/components/schemas/TimesheetEntity'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TimesheetEditForm'))]
    #[Rest\Post(path: '', name: 'post_timesheet')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\QueryParam(name: 'full', strict: true, nullable: true, description: 'Allows to fetch fully serialized objects including subresources (TimesheetExpanded). Allowed values: true (default: false)')]
    public function postAction(Request $request, ParamFetcherInterface $paramFetcher): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $timesheet = $this->service->createNewTimesheet($user, $request);

        $mode = $this->getTrackingMode();

        $form = $this->createForm(TimesheetApiEditForm::class, $timesheet, [
            'include_rate' => $this->isGranted('edit_rate', $timesheet),
            'include_exported' => $this->isGranted('edit_export', $timesheet),
            'include_billable' => $this->isGranted('edit_billable', $timesheet),
            'include_user' => $this->isGranted('create_other_timesheet'),
            'allow_begin_datetime' => $mode->canUpdateTimesWithAPI(),
            'allow_end_datetime' => $mode->canUpdateTimesWithAPI(),
            'date_format' => self::DATE_FORMAT,
        ]);

        $form->submit($request->request->all(), false);

        if ($form->isValid()) {
            try {
                $this->service->saveNewTimesheet($timesheet);

                $view = new View($timesheet, 200);

                if (null !== $paramFetcher->get('full')) {
                    $view->getContext()->setGroups(self::GROUPS_ENTITY_FULL);
                } else {
                    $view->getContext()->setGroups(self::GROUPS_ENTITY);
                }

                return $this->viewHandler->handle($view);
            } catch (ValidationFailedException $ex) {
                $form->addError(new FormError($ex->getMessage()));
            }
        }

        $view = new View($form);
        $view->getContext()->setGroups(self::GROUPS_FORM);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing timesheet record
     */
    #[IsGranted('edit', 'timesheet')]
    #[OA\Patch(description: 'Update an existing timesheet record, you can pass all or just a subset of the attributes.', responses: [new OA\Response(response: 200, description: 'Returns the updated timesheet', content: new OA\JsonContent(ref: '#/components/schemas/TimesheetEntity'))])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Timesheet record ID to update', required: true)]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TimesheetEditForm'))]
    #[Rest\Patch(path: '/{id}', name: 'patch_timesheet', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function patchAction(Request $request, Timesheet $timesheet): Response
    {
        $event = new TimesheetMetaDefinitionEvent($timesheet);
        $this->dispatcher->dispatch($event);

        $mode = $this->getTrackingMode();

        $form = $this->createForm(TimesheetApiEditForm::class, $timesheet, [
            'include_rate' => $this->isGranted('edit_rate', $timesheet),
            'include_exported' => $this->isGranted('edit_export', $timesheet),
            'include_billable' => $this->isGranted('edit_billable', $timesheet),
            'include_user' => $this->isGranted('edit', $timesheet),
            'allow_begin_datetime' => $mode->canUpdateTimesWithAPI(),
            'allow_end_datetime' => $mode->canUpdateTimesWithAPI(),
            'date_format' => self::DATE_FORMAT,
        ]);

        $form->setData($timesheet);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(self::GROUPS_FORM);

            return $this->viewHandler->handle($view);
        }

        $this->service->updateTimesheet($timesheet);

        $view = new View($timesheet, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Delete an existing timesheet record
     */
    #[IsGranted('delete', 'timesheet')]
    #[OA\Delete(responses: [new OA\Response(response: 204, description: 'Delete one timesheet record')])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Timesheet record ID to delete', required: true)]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\Delete(path: '/{id}', name: 'delete_timesheet', requirements: ['id' => '\d+'])]
    public function deleteAction(Timesheet $timesheet): Response
    {
        $this->service->deleteTimesheet($timesheet);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns the collection of recent user activities
     */
    #[IsGranted('view_own_timesheet')]
    #[OA\Response(response: 200, description: 'Returns the collection of recent user activities (always the latest entry of a unique working set grouped by customer, project and activity)', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/TimesheetCollectionExpanded')))]
    #[Rest\Get(path: '/recent', name: 'recent_timesheet')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\QueryParam(name: 'begin', requirements: [new Constraints\DateTime(format: 'Y-m-d\TH:i:s')], strict: true, nullable: true, description: 'Only records after this date will be included. Default: today - 1 year (format: HTML5)')]
    #[Rest\QueryParam(name: 'size', requirements: '\d+', strict: true, nullable: true, description: 'The amount of entries (default: 10)')]
    public function recentAction(ParamFetcherInterface $paramFetcher): Response
    {
        $user = $this->getUser();
        $begin = null;
        $limit = 10;

        $reqLimit = $paramFetcher->get('size');
        if (\is_string($reqLimit) && $reqLimit !== '') {
            $limit = (int) $reqLimit;
        }

        if (null !== ($reqBegin = $paramFetcher->get('begin'))) {
            $begin = $this->getDateTimeFactory($user)->createDateTime($reqBegin);
        }

        $data = $this->repository->getRecentActivities($user, $begin, $limit);

        $recentActivity = new RecentActivityEvent($user, $data);
        $this->dispatcher->dispatch($recentActivity);

        $view = new View($recentActivity->getRecentActivities(), 200);
        $view->getContext()->setGroups(self::GROUPS_COLLECTION_FULL);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns the collection of active timesheet records
     */
    #[IsGranted('view_own_timesheet')]
    #[OA\Response(response: 200, description: 'Returns the collection of active timesheet records for the current user', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/TimesheetCollectionExpanded')))]
    #[Rest\Get(path: '/active', name: 'active_timesheet')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function activeAction(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = $this->repository->getActiveEntries($user);

        $view = new View($data, 200);
        $view->getContext()->setGroups(self::GROUPS_COLLECTION_FULL);

        return $this->viewHandler->handle($view);
    }

    /**
     * Stops an active timesheet record
     */
    #[IsGranted('stop', 'timesheet')]
    #[OA\Response(response: 200, description: 'Stops an active timesheet record and returns it afterwards.', content: new OA\JsonContent(ref: '#/components/schemas/TimesheetEntity'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Timesheet record ID to stop', required: true)]
    #[Rest\Patch(path: '/{id}/stop', name: 'stop_timesheet', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function stopAction(Timesheet $timesheet): Response
    {
        $this->service->stopTimesheet($timesheet);

        $view = new View($timesheet, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Restarts a previously stopped timesheet record for the current user
     */
    #[IsGranted('start', 'timesheet')]
    #[OA\Response(response: 200, description: 'Restarts a timesheet record for the same customer, project, activity combination. The current user will be the owner of the new record. Kimai tries to stop running records, which is expected to fail depending on the configured rules. Data will be copied from the original record if requested.', content: new OA\JsonContent(ref: '#/components/schemas/TimesheetEntity'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Timesheet record ID to restart', required: true)]
    #[Rest\Patch(path: '/{id}/restart', name: 'restart_timesheet', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\RequestParam(name: 'copy', requirements: 'all', strict: true, nullable: true, description: 'Whether data should be copied to the new entry. Allowed values: all (default: nothing is copied)')]
    #[Rest\RequestParam(name: 'begin', requirements: [new Constraints\DateTime(format: 'Y-m-d\TH:i:s')], strict: true, nullable: true, description: 'Changes the restart date to the given one (default: now)')]
    public function restartAction(Timesheet $timesheet, ParamFetcherInterface $paramFetcher): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $copyTimesheet = $this->service->createNewTimesheet($user);

        $factory = $this->getDateTimeFactory();

        $begin = $factory->createDateTime();
        if (null !== ($beginTmp = $paramFetcher->get('begin'))) {
            $begin = $factory->createDateTime($beginTmp);
        }

        $copyTimesheet
            ->setBegin($begin)
            ->setActivity($timesheet->getActivity())
            ->setProject($timesheet->getProject())
        ;

        $copy = $paramFetcher->get('copy');
        if ($copy === 'all') {
            $copyTimesheet->setHourlyRate($timesheet->getHourlyRate());
            $copyTimesheet->setFixedRate($timesheet->getFixedRate());
            $copyTimesheet->setDescription($timesheet->getDescription());
            $copyTimesheet->setBillable($timesheet->isBillable());

            foreach ($timesheet->getTags() as $tag) {
                $copyTimesheet->addTag($tag);
            }

            foreach ($timesheet->getMetaFields() as $metaField) {
                $metaNew = clone $metaField;
                $copyTimesheet->setMetaField($metaNew);
            }
        }

        // needs to be executed AFTER copying the values!
        // the event triggered in prepareNewTimesheet() will add meta fields first. Afterwards
        // setMetaField() will merge meta fields and if we merge in the existing ones from the copied record,
        // it will fail, because they do not have a type assigned
        $this->service->prepareNewTimesheet($copyTimesheet);

        $this->service->restartTimesheet($copyTimesheet, $timesheet);

        $view = new View($copyTimesheet, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Duplicates an existing timesheet record
     */
    #[IsGranted('duplicate', 'timesheet')]
    #[OA\Response(response: 200, description: 'Duplicates a timesheet record, resetting the export state only.', content: new OA\JsonContent(ref: '#/components/schemas/TimesheetEntity'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Timesheet record ID to duplicate', required: true)]
    #[Rest\Patch(path: '/{id}/duplicate', name: 'duplicate_timesheet', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function duplicateAction(Timesheet $timesheet): Response
    {
        $copyTimesheet = clone $timesheet;

        $this->dispatcher->dispatch(new TimesheetDuplicatePreEvent($copyTimesheet, $timesheet));
        $this->service->saveNewTimesheet($copyTimesheet);
        $this->dispatcher->dispatch(new TimesheetDuplicatePostEvent($copyTimesheet, $timesheet));

        $view = new View($copyTimesheet, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Switch the export state of a timesheet record to (un-)lock it
     */
    #[IsGranted('edit_export', 'timesheet')]
    #[OA\Response(response: 200, description: 'Switches the exported state on the record and therefor locks / unlocks it for further updates. Needs edit_export_*_timesheet permission.', content: new OA\JsonContent(ref: '#/components/schemas/TimesheetEntity'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Timesheet record ID to switch export state', required: true)]
    #[Rest\Patch(path: '/{id}/export', name: 'export_timesheet', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function exportAction(Timesheet $timesheet): Response
    {
        if ($timesheet->isExported() && !$this->isGranted('edit_exported_timesheet')) {
            throw $this->createAccessDeniedException('User cannot edit an exported timesheet');
        }

        $timesheet->setExported(!$timesheet->isExported());

        $this->service->updateTimesheet($timesheet);

        $view = new View($timesheet, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Sets the value of a meta-field for an existing timesheet.
     */
    #[IsGranted('edit', 'timesheet')]
    #[OA\Response(response: 200, description: 'Sets the value of an existing/configured meta-field. You cannot create unknown meta-fields, if the given name is not a configured meta-field, this will return an exception.', content: new OA\JsonContent(ref: '#/components/schemas/TimesheetEntity'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Timesheet record ID to set the meta-field value for', required: true)]
    #[Rest\Patch(path: '/{id}/meta', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\RequestParam(name: 'name', strict: true, nullable: false, description: 'The meta-field name')]
    #[Rest\RequestParam(name: 'value', strict: true, nullable: false, description: 'The meta-field value')]
    public function metaAction(Timesheet $timesheet, ParamFetcherInterface $paramFetcher): Response
    {
        $event = new TimesheetMetaDefinitionEvent($timesheet);
        $this->dispatcher->dispatch($event);

        $name = $paramFetcher->get('name');

        if (!\is_string($name) || null === ($meta = $timesheet->getMetaField($name))) {
            throw $this->createNotFoundException('Unknown meta-field requested');
        }

        $meta->setValue($paramFetcher->get('value'));

        $this->service->updateTimesheet($timesheet);

        $view = new View($timesheet, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }
}
