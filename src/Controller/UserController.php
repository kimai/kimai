<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Configuration\FormConfiguration;
use App\Entity\User;
use App\Event\UserPreferenceDisplayEvent;
use App\Form\Toolbar\UserToolbarForm;
use App\Form\UserCreateType;
use App\Repository\Query\UserQuery;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Controller used to manage users in the admin part of the site.
 *
 * @Route(path="/admin/user")
 * @Security("is_granted('view_user')")
 */
final class UserController extends AbstractController
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;
    /**
     * @var UserRepository
     */
    private $repository;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(UserPasswordEncoderInterface $encoder, UserRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->encoder = $encoder;
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return UserRepository
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    /**
     * @Route(path="/", defaults={"page": 1}, name="admin_user", methods={"GET"})
     * @Route(path="/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_user_paginated", methods={"GET"})
     * @Security("is_granted('view_user')")
     */
    public function indexAction($page, Request $request): Response
    {
        $query = new UserQuery();
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->setData($query);
        $form->submit($request->query->all(), false);

        if (!$form->isValid()) {
            $query->resetByFormError($form->getErrors());
        }

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->getPagerfantaForQuery($query);

        $event = new UserPreferenceDisplayEvent(UserPreferenceDisplayEvent::USERS);
        $this->dispatcher->dispatch($event);

        return $this->render('user/index.html.twig', [
            'entries' => $entries,
            'query' => $query,
            'toolbarForm' => $form->createView(),
            'preferences' => $event->getPreferences(),
        ]);
    }

    private function createNewDefaultUser(FormConfiguration $config): User
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setRoles([User::DEFAULT_ROLE]);
        $user->setTimezone($config->getUserDefaultTimezone());
        $user->setLanguage($config->getUserDefaultLanguage());

        return $user;
    }

    /**
     * @Route(path="/create", name="admin_user_create", methods={"GET", "POST"})
     * @Security("is_granted('create_user')")
     */
    public function createAction(Request $request, FormConfiguration $config): Response
    {
        $user = $this->createNewDefaultUser($config);
        $editForm = $this->getCreateUserForm($user);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $password = $this->encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->flashSuccess('action.update.success');

            if ($editForm->get('create_more')->getData() !== true) {
                return $this->redirectToRoute('user_profile_edit', ['username' => $user->getUsername()]);
            }

            $firstUser = $user;
            $user = $this->createNewDefaultUser($config);
            $user->setLanguage($firstUser->getLanguage());
            $user->setTimezone($firstUser->getTimezone());

            $editForm = $this->getCreateUserForm($user);
            $editForm->get('create_more')->setData(true);
        }

        return $this->render(
            'user/edit.html.twig',
            [
                'user' => $user,
                'form' => $editForm->createView()
            ]
        );
    }

    /**
     * @Route(path="/{id}/delete", name="admin_user_delete", methods={"GET", "POST"})
     * @Security("is_granted('delete', userToDelete)")
     */
    public function deleteAction(User $userToDelete, Request $request, TimesheetRepository $repository): Response
    {
        // $userToDelete MUST not be called $user, as $user is always the current user!
        $stats = $repository->getUserStatistics($userToDelete);

        $deleteForm = $this->createFormBuilder(null, [
                'attr' => [
                    'data-form-event' => 'kimai.userUpdate kimai.userDelete',
                    'data-msg-success' => 'action.delete.success',
                    'data-msg-error' => 'action.delete.error',
                ]
            ])
            ->setAction($this->generateUrl('admin_user_delete', ['id' => $userToDelete->getId()]))
            ->setMethod('POST')
            ->getForm();

        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($userToDelete);
            $entityManager->flush();

            $this->flashSuccess('action.delete.success');

            return $this->redirectToRoute('admin_user');
        }

        return $this->render(
            'user/delete.html.twig',
            [
                'user' => $userToDelete,
                'stats' => $stats,
                'form' => $deleteForm->createView(),
            ]
        );
    }

    protected function getToolbarForm(UserQuery $query): FormInterface
    {
        return $this->createForm(UserToolbarForm::class, $query, [
            'action' => $this->generateUrl('admin_user', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
        ]);
    }

    private function getCreateUserForm(User $user): FormInterface
    {
        return $this->createForm(UserCreateType::class, $user, [
            'action' => $this->generateUrl('admin_user_create'),
            'method' => 'POST',
            'include_active_flag' => true,
            'include_preferences' => $this->isGranted('preferences', $user),
            'include_add_more' => true,
        ]);
    }
}
