<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Repository\TagRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("Tag")
 */
class TagController extends BaseApiController
{
    /**
     * @var TagRepository
     */
    protected $repository;

    /**
     * @var ViewHandlerInterface
     */
    protected $viewHandler;

    /**
     * @param ViewHandlerInterface $viewHandler
     * @param TagRepository $repository
     */
    public function __construct(ViewHandlerInterface $viewHandler, TagRepository $repository)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
    }

    /**
     * @SWG\Response(
     *      response=200,
     *      description="Returns the collection of all existing tags",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/TagEntity")
     *      )
     * )
     *
     * @Security("is_granted('view_tags')")
     *
     * @return Response
     *
     * @Rest\Get(path="/tags")
     */
    public function cgetAction()
    {
        $data = $this->repository->findAllTagNames();
        if (null === $data) {
            $data = [];
        }
        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection']);

        return $this->viewHandler->handle($view);
    }
}
