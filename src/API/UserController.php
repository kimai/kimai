<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Event\PrepareUserEvent;
use App\Form\API\UserApiCreateForm;
use App\Form\API\UserApiEditForm;
use App\Repository\AccessTokenRepository;
use App\Repository\Query\UserQuery;
use App\Repository\UserRepository;
use App\User\UserService;
use App\Utils\SearchTerm;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use OpenApi\Attributes as OA;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/users')]
#[IsGranted('API')]
#[OA\Tag(name: 'User')]
final class UserController extends BaseApiController
{
    public const GROUPS_ENTITY = ['Default', 'Entity', 'User', 'User_Entity'];
    public const GROUPS_FORM = ['Default', 'Entity', 'User', 'User_Entity'];
    public const GROUPS_COLLECTION = ['Default', 'Collection', 'User'];
    public const GROUPS_COLLECTION_FULL = ['Default', 'Collection', 'User', 'User_Entity'];

    public function __construct(
        private readonly ViewHandlerInterface $viewHandler,
        private readonly UserRepository $repository,
    ) {
    }

    /**
     * Returns the collection of users (which are visible to the user)
     */
    #[IsGranted('view_user')]
    #[OA\Response(response: 200, description: 'Returns the collection of users. Required permission: view_user', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/UserCollection')))]
    #[Route(methods: ['GET'], path: '', name: 'get_users')]
    #[Rest\QueryParam(name: 'visible', requirements: '1|2|3', default: 1, strict: true, nullable: true, description: 'Visibility status to filter users: 1=visible, 2=hidden, 3=all')]
    #[Rest\QueryParam(name: 'orderBy', requirements: 'id|username|alias|email', strict: true, nullable: true, description: 'The field by which results will be ordered. Allowed values: id, username, alias, email (default: username)')]
    #[Rest\QueryParam(name: 'order', requirements: 'ASC|DESC', strict: true, nullable: true, description: 'The result order. Allowed values: ASC, DESC (default: ASC)')]
    #[Rest\QueryParam(name: 'term', description: 'Free search term')]
    #[Rest\QueryParam(name: 'full', requirements: '0|1|true|false', strict: true, nullable: true, description: 'Allows to fetch full objects including subresources. Allowed values: 0|1|false|true (default: false)')]
    public function cgetAction(ParamFetcherInterface $paramFetcher): Response
    {
        $query = new UserQuery();
        $query->setCurrentUser($this->getUser());

        $visible = $paramFetcher->get('visible');
        if (\is_string($visible) && $visible !== '') {
            $query->setVisibility((int) $visible);
        }

        $order = $paramFetcher->get('order');
        if (\is_string($order) && $order !== '') {
            $query->setOrder($order);
        }

        $orderBy = $paramFetcher->get('orderBy');
        if (\is_string($orderBy) && $orderBy !== '') {
            $query->setOrderBy($orderBy);
        }

        $term = $paramFetcher->get('term');
        if (\is_string($term) && $term !== '') {
            $query->setSearchTerm(new SearchTerm($term));
        }

        $query->setIsApiCall(true);
        $data = $this->repository->getUsersForQuery($query);
        $view = new View($data, 200);

        $full = $paramFetcher->get('full');
        if ($full === '1' || $full === 'true') {
            $view->getContext()->setGroups(self::GROUPS_COLLECTION_FULL);
        } else {
            $view->getContext()->setGroups(self::GROUPS_COLLECTION);
        }

        return $this->viewHandler->handle($view);
    }

    /**
     * Return one user entity
     */
    #[IsGranted('view', 'profile')]
    #[OA\Response(response: 200, description: 'Return one user entity.', content: new OA\JsonContent(ref: '#/components/schemas/UserEntity'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'User ID to fetch', required: true)]
    #[Route(methods: ['GET'], path: '/{id}', name: 'get_user', requirements: ['id' => '\d+'])]
    public function getAction(User $profile, EventDispatcherInterface $dispatcher): Response
    {
        // we need to prepare the user preferences, which is done via an EventSubscriber
        $event = new PrepareUserEvent($profile);
        $dispatcher->dispatch($event);

        $view = new View($profile, 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Return the current user entity
     */
    #[OA\Response(response: 200, description: 'Return the current user entity.', content: new OA\JsonContent(ref: '#/components/schemas/UserEntity'))]
    #[Route(methods: ['GET'], path: '/me', name: 'me_user')]
    public function meAction(): Response
    {
        $view = new View($this->getUser(), 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new user
     */
    #[IsGranted('create_user')]
    #[OA\Post(description: 'Creates a new user and returns it afterwards')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UserCreateForm'))]
    #[Route(methods: ['POST'], path: '', name: 'post_user')]
    public function postAction(Request $request, UserService $userService): Response
    {
        $user = $userService->createNewUser();

        $form = $this->createForm(UserApiCreateForm::class, $user, [
            'include_roles' => $this->isGranted('roles', $user),
            'include_active_flag' => true,
            'include_preferences' => true,
        ]);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $user = $userService->saveNewUser($user);

            $view = new View($user, 200);
            $view->getContext()->setGroups(self::GROUPS_ENTITY);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(self::GROUPS_FORM);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing user
     */
    #[IsGranted('edit', 'profile')]
    #[OA\Patch(description: 'Update an existing user, you can pass all or just a subset of all attributes (passing roles will replace all existing ones)', responses: [new OA\Response(response: 200, description: 'Returns the updated user', content: new OA\JsonContent(ref: '#/components/schemas/UserEntity'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UserEditForm'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'User ID to update', required: true)]
    #[Route(methods: ['PATCH'], path: '/{id}', name: 'patch_user', requirements: ['id' => '\d+'])]
    public function patchAction(Request $request, User $profile): Response
    {
        $form = $this->createForm(UserApiEditForm::class, $profile, [
            'include_roles' => $this->isGranted('roles', $profile),
            'include_active_flag' => ($profile->getId() !== $this->getUser()->getId()),
            'include_preferences' => $this->isGranted('preferences', $profile),
            'include_supervisor' => $this->isGranted('supervisor', $profile),
            'include_password_reset' => $this->isGranted('password', $profile),
        ]);

        $form->setData($profile);
        $form->submit($request->request->all(), false);

        if (false === $form->isValid()) {
            $view = new View($form, Response::HTTP_OK);
            $view->getContext()->setGroups(self::GROUPS_FORM);

            return $this->viewHandler->handle($view);
        }

        $this->repository->saveUser($profile);

        $view = new View($profile, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Delete an API token for the current user
     */
    #[OA\Delete(responses: [new OA\Response(response: 200, description: 'Success if the token could be deleted.')])]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The API token ID to remove', required: true)]
    #[Route(methods: ['DELETE'], path: '/api-token/{id}', name: 'delete_api_token', requirements: ['id' => '\d+'])]
    public function deleteApiToken(AccessToken $accessToken, AccessTokenRepository $accessTokenRepository): Response
    {
        $user = $this->getUser();
        if (!$this->isGranted('api-token', $user)) {
            throw $this->createAccessDeniedException('User has no access to API tokens');
        }

        if (!$this->isGranted('api-token', $accessToken->getUser())) {
            throw $this->createAccessDeniedException('You are not allowed to delete this access token');
        }

        $accessTokenRepository->deleteAccessToken($accessToken);

        $view = new View(null, Response::HTTP_OK);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }
}
