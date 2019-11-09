<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

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
class UserController extends AbstractController
{
    /**
     * @var UserPasswordEncoderInterface
     */
    protected $encoder;
    /**
     * @var UserRepository
     */
    protected $repository;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

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

    /**
     * @Route(path="/create", name="admin_user_create", methods={"GET", "POST"})
     * @Security("is_granted('create_user')")
     */
    public function createAction(Request $request): Response
    {
        $user = new User();
        $user->setEnabled(true);
        $editForm = $this->createEditForm($user);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $password = $this->encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            $user->setEnabled(true);
            $user->setRoles([User::DEFAULT_ROLE]);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->flashSuccess('action.update.success');

            if ($editForm->get('create_more')->getData() !== true) {
                return $this->redirectToRoute('user_profile_edit', ['username' => $user->getUsername()]);
            }

            $user = new User();
            $editForm = $this->createEditForm($user);
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

    private function createEditForm(User $user): FormInterface 
    {
        return $this->createForm(UserCreateType::class, $user, [
            'action' => $this->generateUrl('admin_user_create'),
            'method' => 'POST',
            'include_active_flag' => true
        ]);
    }
}
