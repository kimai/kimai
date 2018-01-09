<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller\Admin;

use AppBundle\Controller\AbstractController;
use AppBundle\Entity\User;
use AppBundle\Form\Toolbar\UserToolbarForm;
use AppBundle\Form\UserCreateType;
use AppBundle\Repository\Query\UserQuery;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller used to manage users in the admin part of the site.
 *
 * @Route("/admin/user")
 * @Security("has_role('ROLE_SUPER_ADMIN')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class UserController extends AbstractController
{

    /**
     * @param Request $request
     * @return UserQuery
     */
    protected function getQueryForRequest(Request $request)
    {
        $visibility = $request->get('visibility', UserQuery::SHOW_VISIBLE);
        if (strlen($visibility) == 0 || (int)$visibility != $visibility) {
            $visibility = UserQuery::SHOW_BOTH;
        }
        $pageSize = (int) $request->get('pageSize');
        $userRole = $request->get('role');

        $query = new UserQuery();
        $query
            ->setPageSize($pageSize)
            ->setVisibility($visibility)
            ->setRole($userRole)
        ;

        return $query ;
    }

    /**
     * @Route("/", defaults={"page": 1}, name="admin_user")
     * @Route("/page/{page}", requirements={"page": "[1-9]\d*"}, name="admin_user_paginated")
     * @Method("GET")
     * @Security("is_granted('view_all', user)")
     */
    public function indexAction($page, Request $request)
    {
        $query = $this->getQueryForRequest($request);
        $query->setPage($page);

        /* @var $entries Pagerfanta */
        $entries = $this->getDoctrine()->getRepository(User::class)->findByQuery($query);

        return $this->render('admin/user.html.twig', [
            'entries' => $entries,
            'query' => $query,
            'toolbarForm' => $this->getToolbarForm($query)->createView(),
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
            $password = $this->get('security.password_encoder')
                ->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            $user->setRoles([User::DEFAULT_ROLE]);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute('user_profile_edit', ['username' => $user->getUsername()]);
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
     * @param UserQuery $query
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getToolbarForm(UserQuery $query)
    {
        return $this->createForm(
            UserToolbarForm::class,
            $query,
            [
                'action' => $this->generateUrl('admin_user_paginated', [
                    'page' => $query->getPage(),
                ]),
                'method' => 'GET',
            ]
        );
    }

    /**
     * @param User $user
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createEditForm(User $user)
    {
        return $this->createForm(
            UserCreateType::class,
            $user,
            [
                'action' => $this->generateUrl('admin_user_create'),
                'method' => 'POST'
            ]
        );
    }
}
