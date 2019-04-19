<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectQuery;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("Project")
 *
 * @Security("is_granted('api')")
 */
class ProjectController extends BaseApiController
{
    /**
     * @var ProjectRepository
     */
    protected $repository;

    /**
     * @var ViewHandlerInterface
     */
    protected $viewHandler;

    /**
     * @param ViewHandlerInterface $viewHandler
     * @param ProjectRepository $repository
     */
    public function __construct(ViewHandlerInterface $viewHandler, ProjectRepository $repository)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
    }

    /**
     * @SWG\Response(
     *      response=200,
     *      description="Returns the collection of all existing projects",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/ProjectEntity")
     *      )
     * )
     * @Rest\QueryParam(name="customer", requirements="\d+", strict=true, nullable=true, description="Customer ID to filter projects")
     * @Rest\QueryParam(name="visible", requirements="\d+", strict=true, nullable=true, description="Visibility status to filter projects (1=visible, 2=hidden, 3=both)")
     * @Rest\QueryParam(name="order", requirements="ASC|DESC", strict=true, nullable=true, description="The result order (allowed values: 'ASC', 'DESC')")
     * @Rest\QueryParam(name="orderBy", requirements="id|name", strict=true, nullable=true, description="The field by which results will be ordered (allowed values: 'id', 'name')")
     *
     * @param ParamFetcherInterface $paramFetcher
     * @return Response
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher)
    {
        $query = new ProjectQuery();
        $query
            ->setResultType(ProjectQuery::RESULT_TYPE_OBJECTS)
            ->setOrderBy('name')
        ;

        if (null !== ($order = $paramFetcher->get('order'))) {
            $query->setOrder($order);
        }

        if (null !== ($orderBy = $paramFetcher->get('orderBy'))) {
            $query->setOrderBy($orderBy);
        }

        if (null !== ($customer = $paramFetcher->get('customer'))) {
            $query->setCustomer($customer);
        }

        if (null !== ($visible = $paramFetcher->get('visible'))) {
            $query->setVisibility($visible);
        }

        $data = $this->repository->findByQuery($query);
        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection', 'Project']);

        return $this->viewHandler->handle($view);
    }

    /**
     * @SWG\Response(
     *      response=200,
     *      description="Returns one project entity",
     *      @SWG\Schema(ref="#/definitions/ProjectEntity"),
     * )
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
        $view->getContext()->setGroups(['Default', 'Entity', 'Project']);

        return $this->viewHandler->handle($view);
    }
}
