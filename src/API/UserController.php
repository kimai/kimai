<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Repository\Query\UserQuery;
use App\Repository\UserRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @RouteResource("User")
 *
 * @Security("is_granted('ROLE_USER')")
 */
class UserController extends BaseApiController
{
    /**
     * @var UserRepository
     */
    protected $repository;

    /**
     * @var ViewHandlerInterface
     */
    protected $viewHandler;

    /**
     * @param ViewHandlerInterface $viewHandler
     * @param UserRepository $repository
     */
    public function __construct(ViewHandlerInterface $viewHandler, UserRepository $repository)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
    }

    /**
     * Returns the collection of all registered users
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the collection of all registered users. Required permission: view_user",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/UserCollection")
     *      )
     * )
     *
     * @Rest\QueryParam(name="visible", requirements="1|2|3", strict=true, nullable=true, description="Visibility status to filter users. Allowed values: 1=visible, 2=hidden, 3=all (default: 1)")
     * @Rest\QueryParam(name="orderBy", requirements="id|username|alias|email", strict=true, nullable=true, description="The field by which results will be ordered. Allowed values: id, username, alias, email (default: username)")
     * @Rest\QueryParam(name="order", requirements="ASC|DESC", strict=true, nullable=true, description="The result order. Allowed values: ASC, DESC (default: ASC)")
     *
     * @Security("is_granted('view_user')")
     *
     * @return Response
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher)
    {
        $query = new UserQuery();
        $query
            ->setResultType(UserQuery::RESULT_TYPE_OBJECTS)
            ->setOrderBy('username')
        ;

        if (null !== ($visible = $paramFetcher->get('visible'))) {
            $query->setVisibility($visible);
        }

        if (null !== ($order = $paramFetcher->get('order'))) {
            $query->setOrder($order);
        }

        if (null !== ($orderBy = $paramFetcher->get('orderBy'))) {
            $query->setOrderBy($orderBy);
        }

        $data = $this->repository->findByQuery($query);
        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection', 'User']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Return one user entity
     *
     * @SWG\Response(
     *      response=200,
     *      description="Return one user entity. Required permission: view_user",
     *      @SWG\Schema(ref="#/definitions/UserEntity"),
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="User ID to fetch",
     *      required=true,
     * )
     *
     * @param int $id
     * @return Response
     */
    public function getAction($id)
    {
        $user = $this->repository->find($id);

        if (null === $user) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('view', $user)) {
            throw new AccessDeniedHttpException('You are not allowed to view this profile');
        }

        $view = new View($user, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'User']);

        return $this->viewHandler->handle($view);
    }
}
