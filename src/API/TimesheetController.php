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
use Symfony\Component\Validator\Constraints;

/**
 * @RouteResource("Timesheet")
 */
class TimesheetController extends BaseApiController
{
    /**
     * @var TimesheetRepository
     */
    protected $repository;
    /**
     * @var ViewHandlerInterface
     */
    protected $viewHandler;
    /**
     * @var TimesheetConfiguration
     */
    protected $configuration;

    /**
     * @var UserDateTimeFactory
     */
    protected $dateTime;

    /**
     * @param ViewHandlerInterface $viewHandler
     * @param TimesheetRepository $repository
     * @param UserDateTimeFactory $dateTime
     * @param TimesheetConfiguration $configuration
     */
    public function __construct(ViewHandlerInterface $viewHandler, TimesheetRepository $repository, UserDateTimeFactory $dateTime, TimesheetConfiguration $configuration)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
        $this->configuration = $configuration;
        $this->dateTime = $dateTime;
    }

    /**
     * Returns a collection of timesheet records.
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns a collection of timesheets records. Be aware that the datetime fields are given in the users local time including the timezone offset via ISO 8601, read https://www.kimai.org/documentation/rest-api.html to find out more.",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/TimesheetCollection")
     *      )
     * )
     *
     * @Rest\QueryParam(name="user", requirements="\d+|all", strict=true, nullable=true, description="User ID to filter timesheets. Needs permission 'view_other_timesheet', pass 'all' to fetch data for all user (default: current user)")
     * @Rest\QueryParam(name="customer", requirements="\d+", strict=true, nullable=true, description="Customer ID to filter timesheets")
     * @Rest\QueryParam(name="project", requirements="\d+", strict=true, nullable=true, description="Project ID to filter timesheets")
     * @Rest\QueryParam(name="activity", requirements="\d+", strict=true, nullable=true, description="Activity ID to filter timesheets")
     * @Rest\QueryParam(name="page", requirements="\d+", strict=true, nullable=true, description="The page to display, renders a 404 if not found (default: 1)")
     * @Rest\QueryParam(name="size", requirements="\d+", strict=true, nullable=true, description="The amount of entries for each page (default: 25)")
     * @Rest\QueryParam(name="order", requirements="ASC|DESC", strict=true, nullable=true, description="The result order. Allowed values: ASC, DESC (default: DESC)")
     * @Rest\QueryParam(name="orderBy", requirements="id|begin|end|rate", strict=true, nullable=true, description="The field by which results will be ordered. Allowed values: id, begin, end, rate (default: begin)")
     * @Rest\QueryParam(name="begin", requirements=@Constraints\DateTime, strict=true, nullable=true, description="Only records after this date will be included (format: ISO 8601)")
     * @Rest\QueryParam(name="end", requirements=@Constraints\DateTime, strict=true, nullable=true, description="Only records before this date will be included (format: ISO 8601)")
     * @Rest\QueryParam(name="exported", requirements="0|1", strict=true, nullable=true, description="Use this flag if you want to filter for export state. Allowed values: 0=not exported, 1=exported (default: all)")
     * @Rest\QueryParam(name="active", requirements="0|1", strict=true, nullable=true, description="Filter for running/active records. Allowed values: 0=stopped, 1=active. (default: all)")
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

        if (null !== ($order = $paramFetcher->get('order'))) {
            $query->setOrder($order);
        }

        if (null !== ($orderBy = $paramFetcher->get('orderBy'))) {
            $query->setOrderBy($orderBy);
        }

        if (null !== ($begin = $paramFetcher->get('begin'))) {
            $query->setBegin(new \DateTime($begin));
        }

        if (null !== ($end = $paramFetcher->get('end'))) {
            $query->setEnd(new \DateTime($end));
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

        /** @var Pagerfanta $data */
        $data = $this->repository->findByQuery($query);
        $data = (array) $data->getCurrentPageResults();

        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns one timesheet record.
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns one timesheet record. Be aware that the datetime fields are given in the users local time including the timezone offset via ISO 8601, read https://www.kimai.org/documentation/rest-api.html to find out more.",
     *      @SWG\Schema(ref="#/definitions/TimesheetEntity")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Timesheet record ID",
     *      required=true,
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
     * Creates a new timesheet record.
     *
     * @SWG\Post(
     *      description="Creates a new timesheet entry and returns it afterwards. Read more about the date-time format at https://www.kimai.org/documentation/rest-api.html.",
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
            'date_format' => self::DATE_FORMAT,
        ]);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            if (null !== $timesheet->getId()) {
                return new Response('This method does not support updates', Response::HTTP_BAD_REQUEST);
            }

            if (!$this->isGranted('start', $timesheet)) {
                return new Response('You are not allowed to start this timesheet record', Response::HTTP_BAD_REQUEST);
            }

            if (null === $timesheet->getEnd()) {
                $this->repository->stopActiveEntries(
                    $timesheet->getUser(),
                    $this->configuration->getActiveEntriesHardLimit()
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
     * Update an existing timesheet record.
     *
     * @SWG\Patch(
     *      description="Update an existing timesheet record, you can pass all or just a subset of the attributes. Read more about the date-time format at https://www.kimai.org/documentation/rest-api.html.",
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
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Timesheet record ID",
     *      required=true,
     * )
     *
     * @param Request $request
     * @param int $id the timesheet to update
     * @return Response
     */
    public function patchAction(Request $request, int $id)
    {
        $timesheet = $this->repository->find($id);

        if (!$this->isGranted('edit', $timesheet)) {
            throw $this->createAccessDeniedException('User cannot update timesheet');
        }

        $form = $this->createForm(TimesheetEditForm::class, $timesheet, [
            'csrf_protection' => false,
            'include_rate' => $this->isGranted('edit_rate', $timesheet),
            'include_exported' => $this->isGranted('edit_export', $timesheet),
            'date_format' => self::DATE_FORMAT,
        ]);

        $form->setData($timesheet);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

            return $this->viewHandler->handle($view);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($timesheet);
        $entityManager->flush();

        $view = new View($timesheet, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Timesheet']);

        return $this->viewHandler->handle($view);
    }
}
