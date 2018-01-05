<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\UserEditType;
use AppBundle\Form\UserPasswordType;
use AppBundle\Form\UserRolesType;
use Symfony\Component\Form\Form;
use TimesheetBundle\Entity\Timesheet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use TimesheetBundle\Repository\TimesheetRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * User profile controller
 *
 * @Route("/profile")
 * @Security("has_role('ROLE_USER')")
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("/{username}", name="user_profile")
     * @Method("GET")
     * @Security("is_granted('view', user)")
     */
    public function indexAction(User $user)
    {
        return $this->getProfileView($user);
    }

    /**
     * @Route("/{username}/edit", name="user_profile_edit")
     * @Method({"GET", "POST"})
     * @Security("is_granted('edit', user)")
     */
    public function editAction(User $user, Request $request)
    {
        $editForm = $this->createEditForm($user);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute(
                'user_profile', ['username' => $user->getUsername()]
            );
        }

        return $this->getProfileView($user, $editForm, null, null, 'profile');
    }

    /**
     * @Route("/{username}/password", name="user_profile_password")
     * @Method({"GET", "POST"})
     * @Security("is_granted('password', user)")
     */
    public function passwordAction(User $user, Request $request)
    {
        $pwdForm = $this->createPasswordForm($user);
        $pwdForm->handleRequest($request);

        if ($pwdForm->isSubmitted() && $pwdForm->isValid()) {
            $password = $this->get('security.password_encoder')
                ->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute(
                'user_profile', ['username' => $user->getUsername()]
            );
        }

        return $this->getProfileView($user, null, $pwdForm, null, 'password');
    }

    /**
     * @Route("/{username}/roles", name="user_profile_roles")
     * @Method({"GET", "POST"})
     * @Security("is_granted('roles', user)")
     */
    public function rolesAction(User $user, Request $request)
    {
        $rolesForm = $this->createRolesForm($user);
        $rolesForm->handleRequest($request);

        if ($rolesForm->isSubmitted() && $rolesForm->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute(
                'user_profile', ['username' => $user->getUsername()]
            );
        }

        return $this->getProfileView($user, null, null, $rolesForm, 'roles');
    }

    /**
     * FIXME implement profile deletion
     *
     * @Route("/{username}/delete", name="user_profile_delete")
     * @Method({"GET", "POST"})
     * @Security("is_granted('delete', user)")
     */
    public function deleteAction(User $user, Request $request)
    {
        $deleteForm = $this->createDeleteForm($user);

        throw new \Exception('Delete not implemented yet');
    }

    /**
     * @param User $user
     * @param Form|null $editForm
     * @param Form|null $pwdForm
     * @param Form|null $rolesForm
     * @param string $tab
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getProfileView(User $user, Form $editForm = null, Form $pwdForm = null, Form $rolesForm = null, $tab = 'charts')
    {
        /* @var $timesheetRepo TimesheetRepository */
        $timesheetRepo = $this->getDoctrine()->getRepository(Timesheet::class);
        $userStats = $timesheetRepo->getUserStatistics($user);
        $monthlyStats = $timesheetRepo->getMonthlyStats($user);

        $viewVars = [
            'tab' => $tab,
            'user' => $user,
            'stats' => $userStats,
            'years' => $monthlyStats,
            'form' => null,
            'form_password' => null,
            'form_roles' => null,
        ];

        if ($this->isGranted('edit', $user)) {
            $editForm = $editForm ?: $this->createEditForm($user);
            $viewVars['form'] = $editForm->createView();
        }
        if ($this->isGranted('password', $user)) {
            $pwdForm = $pwdForm ?: $this->createPasswordForm($user);
            $viewVars['form_password'] = $pwdForm->createView();
        }
        if ($this->isGranted('roles', $user)) {
            $rolesForm = $rolesForm ?: $this->createRolesForm($user);
            $viewVars['form_roles'] = $rolesForm->createView();
        }

        return $this->render('user/profile.html.twig', $viewVars);
    }

    /**
     * @param User $user
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createEditForm(User $user)
    {
        return $this->createForm(
            UserEditType::class,
            $user,
            [
                'action' => $this->generateUrl('user_profile_edit', ['username' => $user->getUsername()]),
                'method' => 'POST'
            ]
        );
    }

    /**
     * @param User $user
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createRolesForm(User $user)
    {
        return $this->createForm(
            UserRolesType::class,
            $user,
            [
                'action' => $this->generateUrl('user_profile_roles', ['username' => $user->getUsername()]),
                'method' => 'POST',
            ]
        );
    }

    /**
     * @param User $user
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createPasswordForm(User $user)
    {
        return $this->createForm(
            UserPasswordType::class,
            $user,
            [
                'validation_groups' => array('passwordUpdate'),
                'action' => $this->generateUrl('user_profile_password', ['username' => $user->getUsername()]),
                'method' => 'POST'
            ]
        );
    }

    /**
     * @param User $user
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_profile_delete', ['username' => $user->getUsername()]))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
