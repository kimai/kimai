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
use FOS\RestBundle\Request\ParamFetcherInterface;
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
     *      description="Returns the collection of all existing tags as string array",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(type="string")
     *      )
     * )
     *
     * @Rest\QueryParam(name="name", requirements="[a-zA-Z0-9 -\.]+", strict=true, nullable=true, description="Search term to filter tag list")
     *
     * @return Response
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher)
    {
        $filter = $paramFetcher->get('name');

        $data = $this->repository->findAllTagNames($filter);
        if (null === $data) {
            $data = [];
        }
        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Delete an existing tag
     *
     * @SWG\Delete(
     *      @SWG\Response(
     *          response=204,
     *          description="Delete one tag"
     *      ),
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Tag ID to delete",
     *      required=true,
     * )
     *
     * @Security("is_granted('delete_tag')")
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction($id)
    {
        $tag = $this->repository->find($id);

        if (null === $tag) {
            throw new NotFoundException();
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($tag);
        $entityManager->flush();

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }

}
