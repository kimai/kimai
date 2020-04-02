<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Configuration\FormConfiguration;
use App\Entity\User;
use App\Form\API\UserApiCreateForm;
use App\Form\API\UserApiEditForm;
use App\Repository\Query\UserQuery;
use App\Repository\UserRepository;
use App\Utils\SearchTerm;
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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @RouteResource("User")
 * @SWG\Tag(name="User")
 *
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
final class UserController extends BaseApiController
{
    /**
     * @var UserRepository
     */
    private $repository;
    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;
    /**
     * @var FormConfiguration
     */
    private $configuration;

    /**
     * @param ViewHandlerInterface $viewHandler
     * @param UserRepository $repository
     */
    public function __construct(ViewHandlerInterface $viewHandler, UserRepository $repository, UserPasswordEncoderInterface $encoder, FormConfiguration $config)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
        $this->encoder = $encoder;
        $this->configuration = $config;
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
     * @Rest\QueryParam(name="term", description="Free search term")
     *
     * @Security("is_granted('view_user')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function cgetAction(ParamFetcherInterface $paramFetcher): Response
    {
        $query = new UserQuery();

        if (null !== ($visible = $paramFetcher->get('visible'))) {
            $query->setVisibility($visible);
        }

        if (null !== ($order = $paramFetcher->get('order'))) {
            $query->setOrder($order);
        }

        if (null !== ($orderBy = $paramFetcher->get('orderBy'))) {
            $query->setOrderBy($orderBy);
        }

        if (!empty($term = $paramFetcher->get('term'))) {
            $query->setSearchTerm(new SearchTerm($term));
        }

        $data = $this->repository->getUsersForQuery($query);
        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Collection', 'User']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Return one user entity
     *
     * @SWG\Response(
     *      response=200,
     *      description="Return one user entity.",
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
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function getAction(int $id): Response
    {
        $user = $this->repository->find($id);

        if (null === $user) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('view', $user)) {
            throw new AccessDeniedHttpException('You are not allowed to view this profile');
        }

        $view = new View($user, 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'User', 'User_Entity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Return the current user entity
     *
     * @SWG\Response(
     *      response=200,
     *      description="Return the current user entity.",
     *      @SWG\Schema(ref="#/definitions/UserEntity"),
     * )
     *
     * @Rest\Get(path="/users/me")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function meAction(): Response
    {
        $view = new View($this->getUser(), 200);
        $view->getContext()->setGroups(['Default', 'Entity', 'User', 'User_Entity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new user
     *
     * @SWG\Post(
     *      description="Creates a new user and returns it afterwards",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the new created user",
     *          @SWG\Schema(ref="#/definitions/UserEntity",),
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/UserCreateForm")
     * )
     *
     * @Security("is_granted('create_user')")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function postAction(Request $request): Response
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setRoles([User::DEFAULT_ROLE]);
        $user->setTimezone($this->configuration->getUserDefaultTimezone());
        $user->setLanguage($this->configuration->getUserDefaultLanguage());

        $form = $this->createForm(UserApiCreateForm::class, $user, [
            'include_roles' => $this->isGranted('roles', $user),
            'include_active_flag' => true,
            'include_preferences' => $this->isGranted('preferences', $user),
        ]);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $password = $this->encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            $this->repository->saveUser($user);

            $view = new View($user, 200);
            $view->getContext()->setGroups(['Default', 'Entity', 'User', 'User_Entity']);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(['Default', 'Entity', 'User', 'User_Entity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing user
     *
     * @SWG\Patch(
     *      description="Update an existing user, you can pass all or just a subset of all attributes (passing roles will replace all existing ones)",
     *      @SWG\Response(
     *          response=200,
     *          description="Returns the updated user",
     *          @SWG\Schema(ref="#/definitions/UserEntity")
     *      )
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref="#/definitions/UserEditForm")
     * )
     * @SWG\Parameter(
     *      name="id",
     *      in="path",
     *      type="integer",
     *      description="User ID to update",
     *      required=true,
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function patchAction(Request $request, int $id): Response
    {
        $user = $this->repository->getUserById($id);

        if (null === $user) {
            throw new NotFoundException();
        }

        if (!$this->isGranted('edit', $user)) {
            throw new AccessDeniedHttpException('Not allowed to edit user');
        }

        $form = $this->createForm(UserApiEditForm::class, $user, [
            'include_roles' => $this->isGranted('roles', $user),
            'include_active_flag' => ($user->getId() !== $this->getUser()->getId()),
            'include_preferences' => $this->isGranted('preferences', $user),
        ]);

        $form->setData($user);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(['Default', 'Entity', 'User', 'User_Entity']);

            return $this->viewHandler->handle($view);
        }

        $this->repository->saveUser($user);

        $view = new View($user, Response::HTTP_OK);
        $view->getContext()->setGroups(['Default', 'Entity', 'User', 'User_Entity']);

        return $this->viewHandler->handle($view);
    }
}
