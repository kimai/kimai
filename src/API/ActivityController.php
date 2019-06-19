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
use App\Event\ActivityMetaDefinitionEvent;
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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(ViewHandlerInterface $viewHandler, ActivityRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Returns a collection of activities
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns a collection of activity entities",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/ActivityCollection")
     *      )
     * )
     * @Rest\QueryParam(name="project", requirements="\d+", strict=true, nullable=true, description="Project ID to filter activities. If none is provided, all activities will be returned.")
     * @Rest\QueryParam(name="visible", requirements="1|2|3", strict=true, nullable=true, description="Visibility status to filter activities. Allowed values: 1=visible, 2=hidden, 3=all (default: 1)")
     * @Rest\QueryParam(name="globals", requirements="true", strict=true, nullable=true, description="Use if you want to fetch only global activities. Allowed values: true (default: false)")
     * @Rest\QueryParam(name="globalsFirst", requirements="false", strict=true, nullable=true, description="Use if you don't want global activities to be listed first. Allowed values: false (default: true)")
     * @Rest\QueryParam(name="orderBy", requirements="id|name|project", strict=true, nullable=true, description="The field by which results will be ordered. Allowed values: id, name, project (default: name)")
     * @Rest\QueryParam(name="order", requirements="ASC|DESC", strict=true, nullable=true, description="The result order. Allowed values: ASC, DESC (default: ASC)")
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

        if (!empty($project = $paramFetcher->get('project'))) {
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
     * Returns one activity
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns one activity entity",
     *      @SWG\Schema(ref="#/definitions/ActivityEntity"),
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Activity ID to fetch",
     *      required=true,
     * )
     *
     * @param int $id
     * @return Response
     */
    public function getAction($id)
    {
        /** @var Activity $data */
        $data = $this->repository->find($id);

        if (null === $data) {
            throw new NotFoundException();
        }

        // make sure the fields are properly setup and we know, which meta fields
        // should be exposed and which not
        $event = new ActivityMetaDefinitionEvent($data);
        $this->dispatcher->dispatch(ActivityMetaDefinitionEvent::class, $event);

        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'Activity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new activity
     *
     * @SWG\Post(
     *      description="Creates a new activity and returns it afterwards",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the new created activity",
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
            throw new AccessDeniedHttpException('User cannot create activities');
        }

        $activity = new Activity();

        $form = $this->createForm(ActivityEditForm::class, $activity, [
            'csrf_protection' => false,
        ]);

        $form->submit($request->request->all());

        if ($form->isValid()) {
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
     * Update an existing activity
     *
     * @SWG\Patch(
     *      description="Update an existing activity, you can pass all or just a subset of all attributes",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the updated activity",
     *          @SWG\Schema(ref="#/definitions/ActivityEntity")
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/ActivityEditForm")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="Activity ID to update",
     *      required=true,
     * )
     *
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function patchAction(Request $request, string $id)
    {
        $activity = $this->repository->find($id);

        if (null === $activity) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $activity)) {
            throw new AccessDeniedHttpException('User cannot update activity');
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
