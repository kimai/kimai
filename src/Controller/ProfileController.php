<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Form\UserApiTokenType;
use App\Form\UserEditType;
use App\Form\UserPasswordType;
use App\Form\UserPreferencesForm;
use App\Form\UserRolesType;
use App\Repository\TimesheetRepository;
use App\Voter\UserVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * User profile controller
 *
 * @Route(path="/profile")
 * @Security("is_granted('ROLE_USER')")
 */
class ProfileController extends AbstractController
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
     * @Route(path="/{username}", name="user_profile", methods={"GET"})
     * @Security("is_granted('view', profile)")
     */
    public function indexAction(User $profile)
    {
        return $this->getProfileView($profile, 'charts');
    }

    /**
     * @Route(path="/{username}/edit", name="user_profile_edit", methods={"GET", "POST"})
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

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile', ['username' => $profile->getUsername()]);
        }

        return $this->getProfileView($profile, 'settings', $form);
    }

    /**
     * @Route(path="/{username}/password", name="user_profile_password", methods={"GET", "POST"})
     * @Security("is_granted('password', profile)")
     */
    public function passwordAction(User $profile, Request $request)
    {
        $form = $this->createPasswordForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $this->encoder->encodePassword($profile, $profile->getPlainPassword());
            $profile->setPassword($password);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile', ['username' => $profile->getUsername()]);
        }

        return $this->getProfileView($profile, 'password', null, $form);
    }

    /**
     * @Route(path="/{username}/api-token", name="user_profile_api_token", methods={"GET", "POST"})
     * @Security("is_granted('api-token', profile)")
     */
    public function apiTokenAction(User $profile, Request $request)
    {
        $form = $this->createApiTokenForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $this->encoder->encodePassword($profile, $profile->getPlainApiToken());
            $profile->setApiToken($password);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile', ['username' => $profile->getUsername()]);
        }

        return $this->getProfileView($profile, 'api-token', null, null, null, null, $form);
    }

    /**
     * @Route(path="/{username}/roles", name="user_profile_roles", methods={"GET", "POST"})
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

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile', ['username' => $profile->getUsername()]);
        }

        return $this->getProfileView($profile, 'roles', null, null, $form);
    }

    /**
     * @Route(path="/{username}/prefs", name="user_profile_preferences", methods={"GET", "POST"})
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

            $profile->setPreferences($preferences);
            $entityManager->persist($profile);
            $entityManager->flush();

            $this->flashSuccess('action.update.success');

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
     * @param Form|null $apiTokenForm
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getProfileView(
        User $user,
        string $tab,
        Form $editForm = null,
        Form $pwdForm = null,
        Form $rolesForm = null,
        Form $prefsForm = null,
        Form $apiTokenForm = null
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
        if ($this->isGranted(UserVoter::API_TOKEN, $user)) {
            $apiTokenForm = $apiTokenForm ?: $this->createApiTokenForm($user);
            $viewVars['forms']['api-token'] = $apiTokenForm->createView();
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
                'validation_groups' => ['passwordUpdate'],
                'action' => $this->generateUrl('user_profile_password', ['username' => $user->getUsername()]),
                'method' => 'POST'
            ]
        );
    }

    /**
     * @param User $user
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createApiTokenForm(User $user)
    {
        return $this->createForm(
            UserApiTokenType::class,
            $user,
            [
                'validation_groups' => ['apiTokenUpdate'],
                'action' => $this->generateUrl('user_profile_api_token', ['username' => $user->getUsername()]),
                'method' => 'POST'
            ]
        );
    }
}
