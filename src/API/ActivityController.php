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
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation as API;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("Activity")
 *
 * @Security("is_granted('ROLE_USER')")
 */
class ActivityController extends Controller
{
    /**
     * @var ActivityRepository
     */
    protected $repository;

    /**
     * @var ViewHandler
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

        return $this->viewHandler->handle($view);
    }
}
