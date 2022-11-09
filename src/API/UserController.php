<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Configuration\SystemConfiguration;
use App\Entity\User;
use App\Event\PrepareUserEvent;
use App\Form\API\UserApiCreateForm;
use App\Form\API\UserApiEditForm;
use App\Repository\Query\UserQuery;
use App\Repository\UserRepository;
use App\Utils\SearchTerm;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/users')]
#[Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")]
#[OA\Tag(name: 'User')]
final class UserController extends BaseApiController
{
    public const GROUPS_ENTITY = ['Default', 'Entity', 'User', 'User_Entity'];
    public const GROUPS_FORM = ['Default', 'Entity', 'User', 'User_Entity'];
    public const GROUPS_COLLECTION = ['Default', 'Collection', 'User'];

    public function __construct(
        private ViewHandlerInterface $viewHandler,
        private UserRepository $repository,
        private UserPasswordHasherInterface $passwordHasher,
        private SystemConfiguration $configuration
    ) {
    }

    /**
     * Returns the collection of all registered users
     */
    #[Security("is_granted('view_user')")]
    #[OA\Response(response: 200, description: 'Returns the collection of all registered users. Required permission: view_user', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/UserCollection')))]
    #[Rest\Get(path: '', name: 'get_users')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    #[Rest\QueryParam(name: 'visible', requirements: '1|2|3', strict: true, nullable: true, description: 'Visibility status to filter users. Allowed values: 1=visible, 2=hidden, 3=all (default: 1)')]
    #[Rest\QueryParam(name: 'orderBy', requirements: 'id|username|alias|email', strict: true, nullable: true, description: 'The field by which results will be ordered. Allowed values: id, username, alias, email (default: username)')]
    #[Rest\QueryParam(name: 'order', requirements: 'ASC|DESC', strict: true, nullable: true, description: 'The result order. Allowed values: ASC, DESC (default: ASC)')]
    #[Rest\QueryParam(name: 'term', description: 'Free search term')]
    public function cgetAction(ParamFetcherInterface $paramFetcher): Response
    {
        $query = new UserQuery();
        $query->setCurrentUser($this->getUser());

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
        $view->getContext()->setGroups(self::GROUPS_COLLECTION);

        return $this->viewHandler->handle($view);
    }

    /**
     * Return one user entity
     */
    #[Security("is_granted('view', profile)")]
    #[OA\Response(response: 200, description: 'Return one user entity.', content: new OA\JsonContent(ref: '#/components/schemas/UserEntity'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'User ID to fetch', required: true)]
    #[Rest\Get(path: '/{id}', name: 'get_user', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
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
    #[Rest\Get(path: '/me', name: 'me_user')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function meAction(): Response
    {
        $view = new View($this->getUser(), 200);
        $view->getContext()->setGroups(self::GROUPS_ENTITY);

        return $this->viewHandler->handle($view);
    }

    /**
     * Creates a new user
     */
    #[Security("is_granted('create_user')")]
    #[OA\Post(description: 'Creates a new user and returns it afterwards')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UserCreateForm'))]
    #[Rest\Post(path: '', name: 'post_user')]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
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
            'include_preferences' => true,
        ]);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $password = $this->passwordHasher->hashPassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            if ($user->getPlainApiToken() !== null) {
                $user->setApiToken($this->passwordHasher->hashPassword($user, $user->getPlainApiToken()));
            }

            $this->repository->saveUser($user);

            $view = new View($user, 200);
            $view->getContext()->setGroups(self::GROUPS_ENTITY);

            $user->eraseCredentials();

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);
        $view->getContext()->setGroups(self::GROUPS_FORM);

        return $this->viewHandler->handle($view);
    }

    /**
     * Update an existing user
     */
    #[Security("is_granted('edit', profile)")]
    #[OA\Patch(description: 'Update an existing user, you can pass all or just a subset of all attributes (passing roles will replace all existing ones)', responses: [new OA\Response(response: 200, description: 'Returns the updated user', content: new OA\JsonContent(ref: '#/components/schemas/UserEntity'))])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UserEditForm'))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'User ID to update', required: true)]
    #[Rest\Patch(path: '/{id}', name: 'patch_user', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function patchAction(Request $request, User $profile): Response
    {
        $form = $this->createForm(UserApiEditForm::class, $profile, [
            'include_roles' => $this->isGranted('roles', $profile),
            'include_active_flag' => ($profile->getId() !== $this->getUser()->getId()),
            'include_preferences' => $this->isGranted('preferences', $profile),
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
}
