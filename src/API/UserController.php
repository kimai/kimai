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
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Repository\UserRepository;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\ViewHandlerInterface;
use \Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Swagger\Annotations as SWG;

/**
 * @RouteResource("User")
 *
 * TODO Security("is_granted('ROLE_SUPER_ADMIN')")
 * TODO Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class UserController extends Controller
{

    /**
     * @var UserRepository
     */
    protected $repository;

    /**
     * @var ViewHandler
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
     *     response=200,
     *     description="Returns the collection of all registered users",
     *     @SWG\Schema(ref=@Model(type=User::class)),
     * )
     *
     * @return Response
     */
    public function cgetAction()
    {
        $data = $this->repository->findAll();
        $view = new View($data, 200);
        return $this->viewHandler->handle($view);
    }

    /**
     * @SWG\Response(
     *     response=200,
     *     description="Return one user entity",
     *     @SWG\Schema(ref=@Model(type=User::class)),
     * )
     *
     * @param int $id
     * @return Response
     */
    public function getAction(int $id)
    {
        $data = $this->repository->find($id);
        if (null === $data) {
            throw new NotFoundHttpException();
        }
        $view = new View($data, 200);
        return $this->viewHandler->handle($view);
    }
}
