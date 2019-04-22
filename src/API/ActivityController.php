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
use App\Form\ActivityEditForm;
use App\Repository\ActivityRepository;
use App\Repository\Query\ActivityQuery;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
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
     *      response=200,
     *      description="Returns the collection of all existing activities",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/ActivityCollection")
     *      )
     * )
     * @Rest\QueryParam(name="project", requirements="\d+", strict=true, nullable=true, description="Project ID to filter activities. If none is provided, only global activities will be returned.")
     * @Rest\QueryParam(name="visible", requirements="\d+", strict=true, nullable=true, description="Visibility status to filter activities (1=visible, 2=hidden, 3=both)")
     * @Rest\QueryParam(name="globals", requirements="true", strict=true, nullable=true, description="Pass 'true' as string to fetch only global activities")
     * @Rest\QueryParam(name="globalsFirst", requirements="false", strict=true, nullable=true, description="Pass 'false' as string if you don't want the global activities to be listed first")
     * @Rest\QueryParam(name="order", requirements="ASC|DESC", strict=true, nullable=true, description="The result order (allowed values: 'ASC', 'DESC')")
     * @Rest\QueryParam(name="orderBy", requirements="id|name|project", strict=true, nullable=true, description="The field by which results will be ordered (allowed values: 'id', 'name', 'project')")
     *
     * @return Response
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher)
    {
        $query = new ActivityQuery();
        $query->setOrderGlobalsFirst(true)
            ->setResultType(ActivityQuery::RESULT_TYPE_OBJECTS)
            ->setOrderBy('name')
        ;

        if (null !== ($order = $paramFetcher->get('order'))) {
            $query->setOrder($order);
        }

        if (null !== ($orderBy = $paramFetcher->get('orderBy'))) {
            $query->setOrderBy($orderBy);
        }

        if (null !== $paramFetcher->get('globals')) {
            $query->setGlobalsOnly(true);
        }

        if ('false' === $paramFetcher->get('globalsFirst')) {
            $query->setOrderGlobalsFirst(false);
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
     *      response=200,
     *      description="Returns one activity entity",
     *      @SWG\Schema(ref="#/definitions/ActivityEntity"),
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

    /**
     * @SWG\Post(
     *      description="Creates a new activity entry and returns it afterwards",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the new created activity entry",
     *          @SWG\Schema(ref="#/definitions/ActivityEntity"),
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/ActivityEditForm")
     * )
     *
     * @param Request $request
     * @return Response
     * @throws \App\Repository\RepositoryException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postAction(Request $request)
    {
        if (!$this->isGranted('create_activity')) {
            throw $this->createAccessDeniedException('User cannot create activities');
        }

        $activity = new Activity();

        $form = $this->createForm(ActivityEditForm::class, $activity, [
            'csrf_protection' => false,
        ]);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            if (null !== $activity->getId()) {
                return new Response('This method does not support updates', Response::HTTP_BAD_REQUEST);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($activity);
            $entityManager->flush();

            $view = new View($activity, 200);
            $view->getContext()->setGroups(['Default', 'Entity', 'Activity']);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(['Default', 'Entity', 'Activity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * @SWG\Patch(
     *      description="Update an existing activity entry, you can pass all or just a subset of all attributes",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the updated activity entry",
     *          @SWG\Schema(ref="#/definitions/ActivityEntity")
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/ActivityEditForm")
     * )
     *
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function patchAction(Request $request, string $id)
    {
        $activity = $this->repository->find($id);

        if (!$this->isGranted('edit', $activity)) {
            throw $this->createAccessDeniedException('User cannot update activity');
        }

        $form = $this->createForm(ActivityEditForm::class, $activity, [
            'csrf_protection' => false,
        ]);

        $form->setData($activity);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(['Default', 'Entity', 'Activity']);

            return $this->viewHandler->handle($view);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($activity);
        $entityManager->flush();

        $view = new View($activity, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'Activity']);

        return $this->viewHandler->handle($view);
    }
}
