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
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Form\TimesheetEditForm;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
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
     * @param ViewHandlerInterface $viewHandler
     * @param TimesheetRepository $repository
     * @param int $hardLimit
     * @param bool $useTags
     */
    public function __construct(ViewHandlerInterface $viewHandler, TimesheetRepository $repository, int $hardLimit, bool $useTags)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
        $this->hardLimit = $hardLimit;
        $this->setTagMode($useTags);
    }

    /**
     * @SWG\Response(
     *      response=200,
     *      description="Returns the collection of all existing timesheets for the user",
     *      @SWG\Schema(ref="#/definitions/TimesheetCollection"),
     * )
     * @Rest\QueryParam(name="customer", requirements="\d+", strict=true, nullable=true, description="Customer ID to filter timesheets")
     * @Rest\QueryParam(name="project", requirements="\d+", strict=true, nullable=true, description="Project ID to filter timesheets")
     * @Rest\QueryParam(name="activity", requirements="\d+", strict=true, nullable=true, description="Activity ID to filter timesheets")
     * @Rest\QueryParam(name="page", requirements="\d+", strict=true, nullable=true, description="The page to display, renders a 404 if not found (default: 1)")
     * @Rest\QueryParam(name="size", requirements="\d+", strict=true, nullable=true, description="The amount of entries for each page (default: 25)")
     * @Rest\QueryParam(name="tags", requirements="[a-zA-Z0-9 -]+", strict=true, nullable=true, description="The name of tags which are in the datasets")
     * @Rest\QueryParam(name="order", requirements="ASC|DESC", strict=true, nullable=true, description="The result order (allowed values: 'ASC', 'DESC')")
     * @Rest\QueryParam(name="orderBy", requirements="id|begin|end|rate", strict=true, nullable=true, description="The field by which results will be ordered (allowed values: 'id', 'begin', 'end', 'rate')")
     *
     * @Security("is_granted('view_own_timesheet')")
     *
     * @return Response
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher)
    {
        $query = new TimesheetQuery();
        $query->setUser($this->getUser());
        $query->setResultType(TimesheetQuery::RESULT_TYPE_PAGER);

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

        if ($this->useTags === TRUE && null !== ($tags = $paramFetcher->get('tags'))) {
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
     *      @SWG\Schema(ref="#/definitions/TimesheetFormEntity"),
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the new created timesheet entry",
     *          @SWG\Schema(ref="#/definitions/TimesheetEntity"),
     *      )
     * )
     *
     * @Security("is_granted('create_own_timesheet')")
     *
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request)
    {
        $timesheet = new Timesheet();
        $timesheet->setUser($this->getUser());
        $timesheet->setBegin(new \DateTime());

        $form = $this->createForm(TimesheetEditForm::class, $timesheet, [
            'csrf_protection' => false,
            'include_rate' => $this->isGranted('edit_rate', $timesheet),
        ]);

        $form->setData($timesheet);
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
}
