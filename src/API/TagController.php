<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\Tag;
use App\Form\API\TagApiEditForm;
use App\Repository\TagRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @RouteResource("Tag")
 * @SWG\Tag(name="Tag")
 *
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
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
     * Fetch all existing tags
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the collection of all existing tags as string array",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(type="string")
     *      )
     * )
     *
     * @Rest\QueryParam(name="name", strict=true, nullable=true, description="Search term to filter tag list")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher): Response
    {
        $filter = $paramFetcher->get('name');

        $data = $this->repository->findAllTagNames($filter);

        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection', 'Tag']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new tag
     *
     * @SWG\Post(
     *      description="Creates a new tag and returns it afterwards",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the new created tag",
     *          @SWG\Schema(ref="#/definitions/TagEntity"),
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/TagEditForm")
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function postAction(Request $request): Response
    {
        if (!$this->isGranted('manage_tag')) {
            throw new AccessDeniedHttpException('User cannot create tags');
        }

        $tag = new Tag();

        $form = $this->createForm(TagApiEditForm::class, $tag);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->repository->saveTag($tag);

            $view = new View($tag, 200);
            $view->getContext()->setGroups(['Default', 'Entity', 'Tag']);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(['Default', 'Entity', 'Tag']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Delete a tag
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
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function deleteAction(int $id): Response
    {
        $tag = $this->repository->find($id);

        if (null === $tag) {
            throw new NotFoundException();
        }

        $this->repository->deleteTag($tag);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $this->viewHandler->handle($view);
    }
}
