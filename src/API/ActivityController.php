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
use App\Repository\ActivityRepository;
use App\Repository\Query\ActivityQuery;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation as API;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("Activity")
 *
 * @Security("is_granted('ROLE_USER')")
 */
class ActivityController extends BaseApiController
{
    /**
     * @var ActivityRepository
     */
    protected $repository;

    /**
     * @var ViewHandlerInterface
     */
    protected $viewHandler;

    /**
     * @param ViewHandlerInterface $viewHandler
     * @param ActivityRepository $repository
     */
    public function __construct(ViewHandlerInterface $viewHandler, ActivityRepository $repository)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
    }

    /**
     * @SWG\Response(
     *     response=200,
     *     description="Returns the collection of all existing activities",
     *     @SWG\Schema(ref=@API\Model(type=Activity::class)),
     * )
     * @Rest\QueryParam(name="project", requirements="\d+", strict=true, nullable=true, description="Project ID to filter activities. If none is provided, only global activities will be returned.")
     * @Rest\QueryParam(name="visible", requirements="\d+", strict=true, nullable=true, description="Visibility status to filter activities")
     * @Rest\QueryParam(name="globals", requirements="true", strict=true, nullable=true, description="Pass true to fetch only global activities")
     *
     * @return Response
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher)
    {
        $query = new ActivityQuery();
        $query->setOrderGlobalsFirst(true)
            ->setResultType(ActivityQuery::RESULT_TYPE_OBJECTS);

        if (null !== ($globals = $paramFetcher->get('globals'))) {
            $query->setGlobalsOnly(true);
        }

        if (null !== ($project = $paramFetcher->get('project'))) {
            $query->setProject($project);
        }

        if (null !== ($visible = $paramFetcher->get('visible'))) {
            $query->setVisibility($visible);
        }

        $data = $this->repository->findByQuery($query);
        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection', 'Activity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * @SWG\Response(
     *     response=200,
     *     description="Returns one activity entity",
     *     @SWG\Schema(ref=@API\Model(type=Activity::class)),
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
        $view->getContext()->setGroups(['Default', 'Entity', 'Activity']);

        return $this->viewHandler->handle($view);
    }
}
