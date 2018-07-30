<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Form\Toolbar\UserToolbarForm;
use App\Form\UserCreateType;
use App\Repository\Query\UserQuery;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Controller used to manage users in the admin part of the site.
 *
 * @Route("/admin/user")
 * @Security("is_granted('ROLE_SUPER_ADMIN')")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class UserController extends AbstractController
{
    /**
     * @var UserPasswordEncoderInterface
     */
    protected $encoder;

    /**
     * @param UserPasswordEncoderInterface $encoder
     */
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @return \App\Repository\UserRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository(User::class);
    }

    /**
     * @Route("/", defaults={"page": 1}, name="admin_user")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_user_paginated")
     * @Method("GET")
     */
    public function indexAction($page, Request $request)
    {
        $query = new UserQuery();
        $query->setPage($page);

        $form = $this->getToolbarForm($query);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UserQuery $query */
            $query = $form->getData();
        }

        /* @var $entries Pagerfanta */
        $entries = $this->getRepository()->findByQuery($query);

        return $this->render('admin/user.html.twig', [
            'entries' => $entries,
            'query' => $query,
            'toolbarForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/create", name="admin_user_create")
     * @Method({"GET", "POST"})
     * @Security("is_granted('create', user)")
     */
    public function createAction(Request $request)
    {
        $user = new User();
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

            $this->flashSuccess('action.updated_successfully');

            if ($editForm->get('create_more')->getData() !== true) {
                return $this->redirectToRoute('user_profile_edit', ['username' => $user->getUsername()]);
            }

            $user = new User();
            $editForm = $this->createEditForm($user);
            $editForm->get('create_more')->setData(true);
        }

        return $this->render(
            'admin/user_edit.html.twig',
            [
                'user' => $user,
                'form' => $editForm->createView()
            ]
        );
    }

    /**
     * The route to delete an existing user.
     *
     * @Route("/{id}/delete", name="admin_user_delete")
     * @Method({"GET", "POST"})
     * @Security("is_granted('delete', userToDelete)")
     *
     * @param User $userToDelete
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function deleteAction(User $userToDelete, Request $request)
    {
        $stats = $this->getDoctrine()->getRepository(Timesheet::class)->getUserStatistics($userToDelete);

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_user_delete', ['id' => $userToDelete->getId()]))
            ->setMethod('POST')
            ->getForm();

        $deleteForm->handleRequest($request);

        if (0 == $stats->getRecordsTotal() || ($deleteForm->isSubmitted() && $deleteForm->isValid())) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($userToDelete);
            $entityManager->flush();

            $this->flashSuccess('action.deleted_successfully');

            return $this->redirectToRoute('admin_user');
        }

        return $this->render(
            'admin/user_delete.html.twig',
            [
                'user' => $userToDelete,
                'stats' => $stats,
                'form' => $deleteForm->createView(),
            ]
        );
    }

    /**
     * @param UserQuery $query
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getToolbarForm(UserQuery $query)
    {
        return $this->createForm(UserToolbarForm::class, $query, [
            'action' => $this->generateUrl('admin_user', [
                'page' => $query->getPage(),
            ]),
            'method' => 'GET',
        ]);
    }

    /**
     * @param User $user
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createEditForm(User $user)
    {
        return $this->createForm(UserCreateType::class, $user, [
            'action' => $this->generateUrl('admin_user_create'),
            'method' => 'POST'
        ]);
    }
}
