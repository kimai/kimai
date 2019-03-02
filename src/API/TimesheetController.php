<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Controller\TagImplementationTrait;
use App\Entity\Timesheet;
use App\Form\TimesheetEditForm;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
use App\Timesheet\UserDateTimeFactory;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("Timesheet")
 */
class TimesheetController extends BaseApiController
{
    use TagImplementationTrait;

    /**
     * @var TimesheetRepository
     */
    protected $repository;

    /**
     * @var ViewHandlerInterface
     */
    protected $viewHandler;

    /**
     * @var int
     */
    protected $hardLimit;

    /**
     * @var UserDateTimeFactory
     */
    protected $dateTime;

    /**
     * @param ViewHandlerInterface $viewHandler
     * @param TimesheetRepository $repository
     * @param UserDateTimeFactory $dateTime
     * @param int $hardLimit
     * @param bool $useTags
     */
    public function __construct(ViewHandlerInterface $viewHandler, TimesheetRepository $repository, UserDateTimeFactory $dateTime, int $hardLimit, bool $useTags)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
        $this->hardLimit = $hardLimit;
        $this->dateTime = $dateTime;
        $this->setTagMode($useTags);
    }

    /**
     * @SWG\Response(
     *      response=200,
     *      description="Returns the collection of all existing timesheets for the user",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/TimesheetEntity")
     *      )
     * )
     *
     * @Rest\QueryParam(name="user", requirements="\d+|all", strict=true, nullable=true, description="User ID to filter timesheets (needs permission 'view_other_timesheet', pass 'all' to fetch data for all user)")
     * @Rest\QueryParam(name="customer", requirements="\d+", strict=true, nullable=true, description="Customer ID to filter timesheets")
     * @Rest\QueryParam(name="project", requirements="\d+", strict=true, nullable=true, description="Project ID to filter timesheets")
     * @Rest\QueryParam(name="activity", requirements="\d+", strict=true, nullable=true, description="Activity ID to filter timesheets")
     * @Rest\QueryParam(name="page", requirements="\d+", strict=true, nullable=true, description="The page to display, renders a 404 if not found (default: 1)")
     * @Rest\QueryParam(name="size", requirements="\d+", strict=true, nullable=true, description="The amount of entries for each page (default: 25)")
     * @Rest\QueryParam(name="tags", requirements="[a-zA-Z0-9 -]+", strict=true, nullable=true, description="The name of tags which are in the datasets")
     * @Rest\QueryParam(name="order", requirements="ASC|DESC", strict=true, nullable=true, description="The result order (allowed values: 'ASC', 'DESC')")
     * @Rest\QueryParam(name="orderBy", requirements="id|begin|end|rate", strict=true, nullable=true, description="The field by which results will be ordered (allowed values: 'id', 'begin', 'end', 'rate')")
     *
     * @Security("is_granted('view_own_timesheet') or is_granted('view_other_timesheet')")
     *
     * @return Response
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher)
    {
        $query = new TimesheetQuery();
        $query->setUser($this->getUser());
        $query->setResultType(TimesheetQuery::RESULT_TYPE_PAGER);

        if ($this->isGranted('view_other_timesheet') && null !== ($user = $paramFetcher->get('user'))) {
            if ('all' === $user) {
                $user = null;
            }
            $query->setUser($user);
        }

        if (null !== ($customer = $paramFetcher->get('customer'))) {
            $query->setCustomer($customer);
        }

        if (null !== ($project = $paramFetcher->get('project'))) {
            $query->setProject($project);
        }

        if (null !== ($activity = $paramFetcher->get('activity'))) {
            $query->setActivity($activity);
        }

        if (null !== ($page = $paramFetcher->get('page'))) {
            $query->setPage($page);
        }

        if (null !== ($size = $paramFetcher->get('size'))) {
            $query->setPageSize($size);
        }

        if ($this->useTags === true && null !== ($tags = $paramFetcher->get('tags'))) {
            $query->setTags($tags);
            $this->prepareTagList($query);
        }

        if (null !== ($order = $paramFetcher->get('order'))) {
            $query->setOrder($order);
        }

        if (null !== ($orderBy = $paramFetcher->get('orderBy'))) {
            $query->setOrderBy($orderBy);
        }

        /** @var Pagerfanta $data */
        $data = $this->repository->findByQuery($query);
        $data = (array) $data->getCurrentPageResults();

        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * @SWG\Response(
     *      response=200,
     *      description="Returns one timesheet entity",
     *      @SWG\Schema(ref="#/definitions/TimesheetEntity")
     * )
     *
     * @Security("is_granted('view_own_timesheet')")
     *
     * @param int $id
     * @return Response
     */
    public function getAction($id)
    {
        $data = $this->repository->find($id);
        if (null === $data) {
            throw new NotFoundException();
        }
        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * @SWG\Post(
     *      description="Creates a new timesheet entry and returns it afterwards",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the new created timesheet entry",
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
     * @param Request $request
     * @return Response
     * @throws \App\Repository\RepositoryException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postAction(Request $request)
    {
        $timesheet = new Timesheet();
        $timesheet->setUser($this->getUser());
        $timesheet->setBegin($this->dateTime->createDateTime());

        $form = $this->createForm(TimesheetEditForm::class, $timesheet, [
            'csrf_protection' => false,
            'include_rate' => $this->isGranted('edit_rate', $timesheet),
            'include_exported' => $this->isGranted('edit_export', $timesheet),
            'use_tags' => $this->isTagMode(),
        ]);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            if (null !== $timesheet->getId()) {
                return new Response('This method does not support updates', Response::HTTP_BAD_REQUEST);
            }

            if (!$this->isGranted('start', $timesheet)) {
                return new Response('You are not allowed to start this timesheet record', Response::HTTP_BAD_REQUEST);
            }

            if ($form->has('duration')) {
                $duration = $form->get('duration')->getData();
                if ($duration > 0) {
                    /** @var Timesheet $record */
                    $record = $form->getData();
                    $end = clone $record->getBegin();
                    $end->modify('+ ' . $duration . 'seconds');
                    $record->setEnd($end);
                }
            }

            if (null === $timesheet->getEnd()) {
                $this->repository->stopActiveEntries(
                    $timesheet->getUser(),
                    $this->hardLimit
                );
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($timesheet);
            $entityManager->flush();

            $view = new View($timesheet, 200);
            $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * @SWG\Patch(
     *      description="Update an existing timesheet entry, you can pass all or just a subset of all attributes",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the updated timesheet entry",
     *          @SWG\Schema(ref="#/definitions/TimesheetEntity")
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/TimesheetEditForm")
     * )
     *
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function patchAction(Request $request, string $id)
    {
        $timesheet = $this->repository->find($id);

        if (!$this->isGranted('edit', $timesheet)) {
            throw $this->createAccessDeniedException('User cannot update timesheet');
        }

        $form = $this->createForm(TimesheetEditForm::class, $timesheet, [
            'csrf_protection' => false,
            'include_rate' => $this->isGranted('edit_rate', $timesheet),
            'include_exported' => $this->isGranted('edit_export', $timesheet),
            'use_tags' => $this->isTagMode(),
        ]);

        $form->setData($timesheet);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

            return $this->viewHandler->handle($view);
        }

        if ($form->has('duration')) {
            $duration = $form->get('duration')->getData();
            if ($duration > 0) {
                /** @var Timesheet $record */
                $record = $form->getData();
                $end = clone $record->getBegin();
                $end->modify('+ ' . $duration . 'seconds');
                $record->setEnd($end);
            }
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($timesheet);
        $entityManager->flush();

        $view = new View($timesheet, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }
}
