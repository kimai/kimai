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
use AppBundle\Repository\UserRepository;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TimesheetBundle\Entity\Timesheet;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
     */
    public function indexAction($username)
    {
        $user = $this->getUserByUsername($username);

        return $this->getProfileView($user);
    }

    /**
     * @param User $user
     * @param Form|null $editForm
     * @param Form|null $pwdForm
     * @param string $tab
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getProfileView(User $user, Form $editForm = null, Form $pwdForm = null, $tab = 'charts')
    {
        /* @var $timesheetRepo TimesheetRepository */
        $timesheetRepo = $this->getDoctrine()->getRepository(Timesheet::class);
        $userStats = $timesheetRepo->getUserStatistics($user);
        $monthlyStats = $timesheetRepo->getMonthlyStats($user);

        $editForm = $editForm !== null ? $editForm : $this->createEditForm($user);
        $pwdForm = $pwdForm !== null ? $pwdForm : $this->createPasswordForm($user);

        return $this->render(
            'user/profile.html.twig',
            [
                'tab' => $tab,
                'user' => $user,
                'stats' => $userStats,
                'years' => $monthlyStats,
                'form' => $editForm->createView(),
                'form_password' => $pwdForm->createView(),
            ]
        );
    }

    protected function getRoles()
    {
        $roles = array();
        foreach ($this->getParameter('security.role_hierarchy.roles') as $key => $value) {
            $roles[] = $key;
            foreach ($value as $value2) {
                $roles[] = $value2;
            }
        }
        $roles = array_unique($roles);
        return $roles;
    }

    /**
     * @Route("/{username}/edit", name="user_profile_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction($username, Request $request)
    {
        $user = $this->getUserByUsername($username);
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

        return $this->getProfileView($user, $editForm, null, 'profile');
    }

    /**
     * @Route("/{username}/password", name="user_profile_password")
     * @Method({"GET", "POST"})
     */
    public function passwordAction($username, Request $request)
    {
        $user = $this->getUserByUsername($username);
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

        return $this->getProfileView($user, null, $pwdForm, 'password');
    }

    /**
     * FIXME implement profile deletion
     * @Route("/{username}/delete", name="user_profile_delete")
     * @Method({"GET", "POST"})
     */
    public function deleteAction($username, Request $request)
    {
        $user = $this->getUserByUsername($username);
        $deleteForm = $this->createDeleteForm($user);

        throw new \Exception('Delete not implemented yet');
    }

    /**
     * @param $username
     * @return User
     * @throws NotFoundHttpException
     */
    protected function getUserByUsername($username)
    {
        $user = $this->getUser();

        // access to own profile always allowed
        if (null === $username) {
            $username = $user->getUsername();
        }

        // only administrator can bypass that part if the requested user is not the current user
        if ($username !== $user->getUsername()) {
            $this->denyUnlessGranted('ROLE_ADMIN');
        }

        // if the user is not the current use, load the requested one
        if ($username !== $user->getUsername()) {
            /* @var $userRepo UserRepository */
            $userRepo = $this->getDoctrine()->getRepository(User::class);
            $user = $userRepo->findByUsername($username);
            if (null === $user) {
                throw new NotFoundHttpException('User "'.$username.'" does not exist');
            }
        }

        return $user;
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
