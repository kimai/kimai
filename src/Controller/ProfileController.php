<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\UserEditType;
use App\Form\UserPasswordType;
use App\Form\UserPreferencesForm;
use App\Form\UserRolesType;
use App\Voter\UserVoter;
use Symfony\Component\Form\Form;
use App\Entity\Timesheet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Repository\TimesheetRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * User profile controller
 *
 * @Route("/profile")
 * @Security("has_role('ROLE_USER')")
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("/{username}", name="user_profile")
     * @Method("GET")
     * @Security("is_granted('view', profile)")
     */
    public function indexAction(User $profile)
    {
        return $this->getProfileView($profile, 'charts');
    }

    /**
     * @Route("/{username}/edit", name="user_profile_edit")
     * @Method({"GET", "POST"})
     * @Security("is_granted('edit', profile)")
     */
    public function editAction(User $profile, Request $request)
    {
        $form = $this->createEditForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute('user_profile', ['username' => $profile->getUsername()]);
        }

        return $this->getProfileView($profile, 'settings', $form);
    }

    /**
     * @Route("/{username}/password", name="user_profile_password")
     * @Method({"GET", "POST"})
     * @Security("is_granted('password', profile)")
     */
    public function passwordAction(User $profile, Request $request)
    {
        $form = $this->createPasswordForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $this->get('security.password_encoder')
                ->encodePassword($profile, $profile->getPlainPassword());
            $profile->setPassword($password);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute('user_profile', ['username' => $profile->getUsername()]);
        }

        return $this->getProfileView($profile, 'password', null, $form);
    }

    /**
     * @Route("/{username}/roles", name="user_profile_roles")
     * @Method({"GET", "POST"})
     * @Security("is_granted('roles', profile)")
     */
    public function rolesAction(User $profile, Request $request)
    {
        $form = $this->createRolesForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute('user_profile', ['username' => $profile->getUsername()]);
        }

        return $this->getProfileView($profile, 'roles', null, null, $form);
    }

    /**
     * @Route("/{username}/prefs", name="user_profile_preferences")
     * @Method({"GET", "POST"})
     * @Security("is_granted('preferences', profile)")
     */
    public function savePreferencesAction(User $profile, Request $request)
    {
        $original = [];
        foreach ($profile->getPreferences() as $preference) {
            $original[$preference->getName()] = $preference;
        }

        $form = $this->createPreferencesForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $preferences = $profile->getPreferences();

            // do not allow to add unknown preferences
            foreach ($preferences as $preference) {
                if (!isset($original[$preference->getName()])) {
                    $preferences->removeElement($preference);
                }
            }

            // but allow to delete already saved settings
            foreach ($original as $name => $preference) {
                if (false === $profile->getPreferences()->contains($preference)) {
                    $entityManager->remove($preference);
                }
            }

            foreach ($preferences as $preference) {
                $preference->setUser($profile);
                $entityManager->persist($preference);
                $entityManager->flush();
            }

            $this->flashSuccess('action.updated_successfully');

            return $this->redirectToRoute('user_profile', ['username' => $profile->getUsername()]);
        }

        return $this->getProfileView($profile, 'preferences', null, null, null, $form);
    }

    /**
     * @param User $user
     * @param string $tab
     * @param Form|null $editForm
     * @param Form|null $pwdForm
     * @param Form|null $rolesForm
     * @param Form|null $prefsForm
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getProfileView(
        User $user,
        string $tab,
        Form $editForm = null,
        Form $pwdForm = null,
        Form $rolesForm = null,
        Form $prefsForm = null
    ) {
        /* @var $timesheetRepo TimesheetRepository */
        $timesheetRepo = $this->getDoctrine()->getRepository(Timesheet::class);
        $userStats = $timesheetRepo->getUserStatistics($user);
        $monthlyStats = $timesheetRepo->getMonthlyStats($user);

        $viewVars = [
            'tab' => $tab,
            'user' => $user,
            'stats' => $userStats,
            'years' => $monthlyStats,
            'forms' => []
        ];

        if ($this->isGranted(UserVoter::EDIT, $user)) {
            $editForm = $editForm ?: $this->createEditForm($user);
            $viewVars['forms']['settings'] = $editForm->createView();
        }
        if ($this->isGranted(UserVoter::PASSWORD, $user)) {
            $pwdForm = $pwdForm ?: $this->createPasswordForm($user);
            $viewVars['forms']['password'] = $pwdForm->createView();
        }
        if ($this->isGranted(UserVoter::ROLES, $user)) {
            $rolesForm = $rolesForm ?: $this->createRolesForm($user);
            $viewVars['forms']['roles'] = $rolesForm->createView();
        }
        if ($this->isGranted(UserVoter::PREFERENCES, $user)) {
            $prefsForm = $prefsForm ?: $this->createPreferencesForm($user);
            $viewVars['forms']['preferences'] = $prefsForm->createView();
        }

        return $this->render('user/profile.html.twig', $viewVars);
    }

    /**
     * @param User $user
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createPreferencesForm(User $user)
    {
        return $this->createForm(
            UserPreferencesForm::class,
            $user,
            [
                'action' => $this->generateUrl('user_profile_preferences', ['username' => $user->getUsername()]),
                'method' => 'POST'
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
}
