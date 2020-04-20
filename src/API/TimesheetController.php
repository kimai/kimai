<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Configuration\TimesheetConfiguration;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Event\TimesheetMetaDefinitionEvent;
use App\Form\API\TimesheetApiEditForm;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TagRepository;
use App\Repository\TimesheetRepository;
use App\Timesheet\RoundingService;
use App\Timesheet\TimesheetService;
use App\Timesheet\TrackingMode\TrackingModeInterface;
use App\Timesheet\TrackingModeService;
use App\Timesheet\UserDateTimeFactory;
use App\Utils\SearchTerm;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @RouteResource("Timesheet")
 * @SWG\Tag(name="Timesheet")
 *
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
class TimesheetController extends BaseApiController
{
    /**
     * @var TimesheetRepository
     */
    private $repository;
    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;
    /**
     * @var TimesheetConfiguration
     */
    private $configuration;
    /**
     * @var UserDateTimeFactory
     */
    private $dateTime;
    /**
     * @var TagRepository
     */
    private $tagRepository;
    /**
     * @var TrackingModeService
     */
    private $trackingModeService;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var RoundingService
     */
    private $roundingService;
    /**
     * @var TimesheetService
     */
    private $service;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        TimesheetRepository $repository,
        UserDateTimeFactory $dateTime,
        TimesheetConfiguration $configuration,
        TagRepository $tagRepository,
        TrackingModeService $trackingModeService,
        EventDispatcherInterface $dispatcher,
        RoundingService $roundingService,
        TimesheetService $service
    ) {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
        $this->configuration = $configuration;
        $this->dateTime = $dateTime;
        $this->tagRepository = $tagRepository;
        $this->trackingModeService = $trackingModeService;
        $this->dispatcher = $dispatcher;
        $this->roundingService = $roundingService;
        $this->service = $service;
    }

    protected function getTrackingMode(): TrackingModeInterface
    {
        return $this->trackingModeService->getActiveMode();
    }

    /**
     * Returns a collection of timesheet records
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns a collection of timesheets records. Be aware that the datetime fields are given in the users local time including the timezone offset via ISO 8601.",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/TimesheetCollection")
     *      )
     * )
     *
     * @Rest\QueryParam(name="user", requirements="\d+|all", strict=true, nullable=true, description="User ID to filter timesheets. Needs permission 'view_other_timesheet', pass 'all' to fetch data for all user (default: current user)")
     * @Rest\QueryParam(name="customer", requirements="\d+", strict=true, nullable=true, description="DEPRECATED: Customer ID to filter timesheets (will be removed with 2.0)")
     * @Rest\QueryParam(name="customers", requirements="[\d|,]+", strict=true, nullable=true, description="Comma separated list of customer IDs to filter timesheets")
     * @Rest\QueryParam(name="project", requirements="\d+", strict=true, nullable=true, description="DEPRECATED: Project ID to filter timesheets (will be removed with 2.0)")
     * @Rest\QueryParam(name="projects", requirements="[\d|,]+", strict=true, nullable=true, description="Comma separated list of project IDs to filter timesheets")
     * @Rest\QueryParam(name="activity", requirements="\d+", strict=true, nullable=true, description="DEPRECATED: Activity ID to filter timesheets (will be removed with 2.0)")
     * @Rest\QueryParam(name="activities", requirements="[\d|,]+", strict=true, nullable=true, description="Comma separated list of activity IDs to filter timesheets")
     * @Rest\QueryParam(name="page", requirements="\d+", strict=true, nullable=true, description="The page to display, renders a 404 if not found (default: 1)")
     * @Rest\QueryParam(name="size", requirements="\d+", strict=true, nullable=true, description="The amount of entries for each page (default: 50)")
     * @Rest\QueryParam(name="tags", strict=true, nullable=true, description="The name of tags which are in the datasets")
     * @Rest\QueryParam(name="orderBy", requirements="id|begin|end|rate", strict=true, nullable=true, description="The field by which results will be ordered. Allowed values: id, begin, end, rate (default: begin)")
     * @Rest\QueryParam(name="order", requirements="ASC|DESC", strict=true, nullable=true, description="The result order. Allowed values: ASC, DESC (default: DESC)")
     * @Rest\QueryParam(name="begin", requirements=@Constraints\DateTime(format="Y-m-d\TH:i:s"), strict=true, nullable=true, description="Only records after this date will be included (format: HTML5)")
     * @Rest\QueryParam(name="end", requirements=@Constraints\DateTime(format="Y-m-d\TH:i:s"), strict=true, nullable=true, description="Only records before this date will be included (format: HTML5)")
     * @Rest\QueryParam(name="exported", requirements="0|1", strict=true, nullable=true, description="Use this flag if you want to filter for export state. Allowed values: 0=not exported, 1=exported (default: all)")
     * @Rest\QueryParam(name="active", requirements="0|1", strict=true, nullable=true, description="Filter for running/active records. Allowed values: 0=stopped, 1=active (default: all)")
     * @Rest\QueryParam(name="full", requirements="true", strict=true, nullable=true, description="Allows to fetch fully serialized objects including subresources (TimesheetSubCollection). Allowed values: true (default: false)")
     * @Rest\QueryParam(name="term", description="Free search term")
     *
     * @Security("is_granted('view_own_timesheet') or is_granted('view_other_timesheet')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher): Response
    {
        $query = new TimesheetQuery();
        $query->setUser($this->getUser());

        if ($this->isGranted('view_other_timesheet') && null !== ($user = $paramFetcher->get('user'))) {
            if ('all' === $user) {
                $user = null;
            }
            $query->setUser($user);
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

        if (!empty($activities = $paramFetcher->get('activities'))) {
            if (!\is_array($activities)) {
                $activities = explode(',', $activities);
            }
            if (!empty($activities)) {
                $query->setActivities($activities);
            }
        }

        if (!empty($activity = $paramFetcher->get('activity'))) {
            $query->addActivity($activity);
        }

        if (null !== ($page = $paramFetcher->get('page'))) {
            $query->setPage($page);
        }

        if (null !== ($size = $paramFetcher->get('size'))) {
            $query->setPageSize($size);
        }

        if (null !== ($tags = $paramFetcher->get('tags'))) {
            $ids = $this->tagRepository->findIdsByTagNameList($tags);
            if ($ids !== null && \count($ids) > 0) {
                $query->setTags(new ArrayCollection($ids));
            }
        }

        if (null !== ($order = $paramFetcher->get('order'))) {
            $query->setOrder($order);
        }

        if (null !== ($orderBy = $paramFetcher->get('orderBy'))) {
            $query->setOrderBy($orderBy);
        }

        if (null !== ($begin = $paramFetcher->get('begin'))) {
            $query->setBegin($this->dateTime->createDateTime($begin));
        }

        if (null !== ($end = $paramFetcher->get('end'))) {
            $query->setEnd($this->dateTime->createDateTime($end));
        }

        if (null !== ($active = $paramFetcher->get('active'))) {
            $active = (int) $active;
            if ($active === 1) {
                $query->setState(TimesheetQuery::STATE_RUNNING);
            } elseif ($active === 0) {
                $query->setState(TimesheetQuery::STATE_STOPPED);
            }
        }

        if (null !== ($exported = $paramFetcher->get('exported'))) {
            $exported = (int) $exported;
            if ($exported === 1) {
                $query->setExported(TimesheetQuery::STATE_EXPORTED);
            } elseif ($exported === 0) {
                $query->setExported(TimesheetQuery::STATE_NOT_EXPORTED);
            }
        }

        if (!empty($term = $paramFetcher->get('term'))) {
            $query->setSearchTerm(new SearchTerm($term));
        }

        /** @var Pagerfanta $data */
        $data = $this->repository->getPagerfantaForQuery($query);
        $data = (array) $data->getCurrentPageResults();

        $view = new View($data, 200);
        if ('true' === $paramFetcher->get('full')) {
            $view->getContext()->setGroups(['Default', 'Subresource', 'Timesheet']);
        } else {
            $view->getContext()->setGroups(['Default', 'Collection', 'Timesheet']);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns one timesheet record
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns one timesheet record. Be aware that the datetime fields are given in the users local time including the timezone offset via ISO 8601.",
     *      @SWG\Schema(ref="#/definitions/TimesheetEntity")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Timesheet record ID to fetch",
     *      required=true,
     * )
     *
     * @Security("is_granted('view_own_timesheet') or is_granted('view_other_timesheet')")
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

        if (!$this->isGranted('view', $data)) {
            throw new AccessDeniedHttpException('You are not allowed to view this timesheet');
        }

        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new timesheet record
     *
     * @SWG\Post(
     *      description="Creates a new timesheet record for the current user and returns it afterwards.",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the new created timesheet",
     *          @SWG\Schema(ref="#/definitions/TimesheetEntity"),
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/TimesheetEditForm")
     * )
     *
     * @Security("is_granted('create_own_timesheet')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function postAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $timesheet = $this->service->createNewTimesheet($user, $request);

        $mode = $this->getTrackingMode();

        $form = $this->createForm(TimesheetApiEditForm::class, $timesheet, [
            'include_rate' => $this->isGranted('edit_rate', $timesheet),
            'include_exported' => $this->isGranted('edit_export', $timesheet),
            'include_user' => $this->isGranted('create_other_timesheet'),
            'allow_begin_datetime' => $mode->canUpdateTimesWithAPI(),
            'allow_end_datetime' => $mode->canUpdateTimesWithAPI(),
            'date_format' => self::DATE_FORMAT,
        ]);

        $form->submit($request->request->all(), false);

        if ($form->isValid()) {
            try {
                $this->service->saveNewTimesheet($timesheet);
            } catch (\Exception $ex) {
                if ($ex->getMessage() === 'timesheet.start.exceeded_limit') {
                    throw new BadRequestHttpException('Too many active timesheets');
                } else {
                    throw $ex;
                }
            }

            $view = new View($timesheet, 200);
            $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing timesheet record
     *
     * @SWG\Patch(
     *      description="Update an existing timesheet record, you can pass all or just a subset of the attributes.",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the updated timesheet",
     *          @SWG\Schema(ref="#/definitions/TimesheetEntity")
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/TimesheetEditForm")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Timesheet record ID to update",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function patchAction(Request $request, int $id): Response
    {
        $timesheet = $this->repository->find($id);

        if (null === $timesheet) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $timesheet)) {
            throw new AccessDeniedHttpException('You are not allowed to update this timesheet');
        }

        $event = new TimesheetMetaDefinitionEvent($timesheet);
        $this->dispatcher->dispatch($event);

        $mode = $this->getTrackingMode();

        $form = $this->createForm(TimesheetApiEditForm::class, $timesheet, [
            'include_rate' => $this->isGranted('edit_rate', $timesheet),
            'include_exported' => $this->isGranted('edit_export', $timesheet),
            'include_user' => $this->isGranted('edit', $timesheet),
            'allow_begin_datetime' => $mode->canUpdateTimesWithAPI(),
            'allow_end_datetime' => $mode->canUpdateTimesWithAPI(),
            'date_format' => self::DATE_FORMAT,
        ]);

        $form->setData($timesheet);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

            return $this->viewHandler->handle($view);
        }

        $this->service->updateTimesheet($timesheet);

        $view = new View($timesheet, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Delete an existing timesheet record
     *
     * @SWG\Delete(
     *      @SWG\Response(
     *          response=204,
     *          description="Delete one timesheet record"
     *      ),
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Timesheet record ID to delete",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function deleteAction(int $id): Response
    {
        $timesheet = $this->repository->find($id);

        if (null === $timesheet) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('delete', $timesheet)) {
            throw $this->createAccessDeniedException('You are not allowed to delete this timesheet');
        }

        $this->service->deleteTimesheet($timesheet);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns the collection of recent user activities
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the collection of recent user activities (always the latest entry of a unique working set grouped by customer, project and activity)",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/TimesheetSubCollection")
     *      )
     * )
     *
     * @Rest\QueryParam(name="user", requirements="\d+|all", strict=true, nullable=true, description="User ID to filter timesheets. Needs permission 'view_other_timesheet', pass 'all' to fetch data for all user (default: current user)")
     * @Rest\QueryParam(name="begin", requirements=@Constraints\DateTime(format="Y-m-d\TH:i:s"), strict=true, nullable=true, description="Only records after this date will be included. Default: today - 1 year (format: HTML5)")
     * @Rest\QueryParam(name="size", requirements="\d+", strict=true, nullable=true, description="The amount of entries (default: 10)")
     *
     * @Security("is_granted('view_own_timesheet') or is_granted('view_other_timesheet')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function recentAction(ParamFetcherInterface $paramFetcher): Response
    {
        $user = $this->getUser();
        $begin = $this->dateTime->createDateTime('-1 year');
        $limit = 10;

        if ($this->isGranted('view_other_timesheet') && null !== ($reqUser = $paramFetcher->get('user'))) {
            if ('all' === $reqUser) {
                $reqUser = null;
            }
            $user = $reqUser;
        }

        if (null !== ($reqLimit = $paramFetcher->get('size'))) {
            $limit = $reqLimit;
        }

        if (null !== ($reqBegin = $paramFetcher->get('begin'))) {
            $begin = $this->dateTime->createDateTime($reqBegin);
        }

        $data = $this->repository->getRecentActivities($user, $begin, $limit);

        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Subresource', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns the collection of active timesheet records
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the collection of active timesheet records for the current user",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/TimesheetSubCollection")
     *      )
     * )
     *
     * @Security("is_granted('view_own_timesheet')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function activeAction(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = $this->repository->getActiveEntries($user);

        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Subresource', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Stops an active timesheet record
     *
     * @SWG\Response(
     *      response=200,
     *      description="Stops an active timesheet record and returns it afterwards.",
     *      @SWG\Schema(ref="#/definitions/TimesheetEntity")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Timesheet record ID to stop",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function stopAction(int $id): Response
    {
        $timesheet = $this->repository->find($id);

        if (null === $timesheet) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('stop', $timesheet)) {
            throw new AccessDeniedHttpException('You are not allowed to stop this timesheet');
        }

        $this->service->stopTimesheet($timesheet);

        $view = new View($timesheet, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Restarts a previously stopped timesheet record for the current user
     *
     * @SWG\Response(
     *      response=200,
     *      description="Restarts a timesheet record for the same customer, project, activity combination. The current user will be the owner of the new record. Kimai tries to stop running records, which is expected to fail depending on the configured rules. Data will be copied from the original record if requested.",
     *      @SWG\Schema(ref="#/definitions/TimesheetEntity")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Timesheet record ID to restart",
     *      required=true,
     * )
     *
     * @Rest\RequestParam(name="copy", requirements="all|tags|rates|meta|description", strict=true, nullable=true, description="Whether data should be copied to the new entry. Allowed values: all, tags, rates, description, meta (default: nothing is copied)")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function restartAction(int $id, ParamFetcherInterface $paramFetcher, ValidatorInterface $validator): Response
    {
        $timesheet = $this->repository->find($id);

        if (null === $timesheet) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('start', $timesheet)) {
            throw new AccessDeniedHttpException('You are not allowed to re-start this timesheet');
        }

        /** @var User $user */
        $user = $this->getUser();

        $copyTimesheet = $this->service->createNewTimesheet($user);

        $copyTimesheet
            ->setBegin($this->dateTime->createDateTime())
            ->setActivity($timesheet->getActivity())
            ->setProject($timesheet->getProject())
        ;
        $this->roundingService->roundBegin($copyTimesheet);

        if (null !== ($copy = $paramFetcher->get('copy'))) {
            if (\in_array($copy, ['rates', 'all'])) {
                $copyTimesheet->setHourlyRate($timesheet->getHourlyRate());
                $copyTimesheet->setFixedRate($timesheet->getFixedRate());
            }

            if (\in_array($copy, ['description', 'all'])) {
                $copyTimesheet->setDescription($timesheet->getDescription());
            }

            if (\in_array($copy, ['tags', 'all'])) {
                foreach ($timesheet->getTags() as $tag) {
                    $copyTimesheet->addTag($tag);
                }
            }

            if (\in_array($copy, ['meta', 'all'])) {
                foreach ($timesheet->getMetaFields() as $metaField) {
                    $metaNew = clone $metaField;
                    $copyTimesheet->setMetaField($metaNew);
                }
            }
        }

        $errors = $validator->validate($copyTimesheet);

        if (\count($errors) > 0) {
            throw new BadRequestHttpException($errors[0]->getPropertyPath() . ' = ' . $errors[0]->getMessage());
        }

        $this->service->saveNewTimesheet($copyTimesheet);

        $view = new View($copyTimesheet, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Duplicates an existing timesheet record
     *
     * @SWG\Response(
     *      response=200,
     *      description="Duplicates a timesheet record, resetting the export state only.",
     *      @SWG\Schema(ref="#/definitions/TimesheetEntity")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Timesheet record ID to duplicate",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function duplicateAction(int $id): Response
    {
        $timesheet = $this->repository->find($id);

        if (null === $timesheet) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('duplicate', $timesheet)) {
            throw new AccessDeniedHttpException('You are not allowed to duplicate this timesheet');
        }

        $copyTimesheet = clone $timesheet;

        $this->service->saveNewTimesheet($copyTimesheet);

        $view = new View($copyTimesheet, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Switch the export state of a timesheet record to (un-)lock it
     *
     * @SWG\Response(
     *      response=200,
     *      description="Switches the exported state on the record and therefor locks / unlocks it for further updates. Needs edit_export_*_timesheet permission.",
     *      @SWG\Schema(ref="#/definitions/TimesheetEntity")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Timesheet record ID to switch export state",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function exportAction(int $id): Response
    {
        $timesheet = $this->repository->find($id);

        if (null === $timesheet) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit_export', $timesheet)) {
            throw new AccessDeniedHttpException(
                sprintf('You are not allowed to %s this timesheet', ($timesheet->isExported() ? 'unlock' : 'lock'))
            );
        }

        $timesheet->setExported(!$timesheet->isExported());

        $this->service->updateTimesheet($timesheet);

        $view = new View($timesheet, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Sets the value of a meta-field for an existing timesheet.
     *
     * @SWG\Response(
     *      response=200,
     *      description="Sets the value of an existing/configured meta-field. You cannot create unknown meta-fields, if the given name is not a configured meta-field, this will return an exception.",
     *      @SWG\Schema(ref="#/definitions/TimesheetEntity")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Timesheet record ID to set the meta-field value for",
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
        $timesheet = $this->repository->find($id);

        if (null === $timesheet) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $timesheet)) {
            throw new AccessDeniedHttpException('You are not allowed to update this timesheet');
        }

        $event = new TimesheetMetaDefinitionEvent($timesheet);
        $this->dispatcher->dispatch($event);

        $name = $paramFetcher->get('name');
        $value = $paramFetcher->get('value');

        if (null === ($meta = $timesheet->getMetaField($name))) {
            throw new \InvalidArgumentException('Unknown meta-field requested');
        }

        $meta->setValue($value);

        $this->service->updateTimesheet($timesheet);

        $view = new View($timesheet, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }
}
