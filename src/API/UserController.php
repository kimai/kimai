<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\User;
use App\Repository\UserRepository;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("User")
 *
 * @Security("is_granted('ROLE_SUPER_ADMIN')")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
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
     * @SWG\Response(
     *      response=200,
     *      description="Returns the collection of all registered users",
     *      @SWG\Schema(ref="#/definitions/UserCollection"),
     * )
     *
     * @return Response
     */
    public function cgetAction()
    {
        $data = $this->repository->findAll();
        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection', 'User']);

        return $this->viewHandler->handle($view);
    }

    /**
     * @SWG\Response(
     *      response=200,
     *      description="Return one user entity",
     *      @SWG\Schema(ref="#/definitions/UserEntity"),
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
        $view->getContext()->setGroups(['Default', 'Entity', 'User']);

        return $this->viewHandler->handle($view);
    }
}
