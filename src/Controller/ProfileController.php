<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Event\PrepareUserEvent;
use App\Form\UserApiTokenType;
use App\Form\UserEditType;
use App\Form\UserPasswordType;
use App\Form\UserPreferencesForm;
use App\Form\UserRolesType;
use App\Form\UserTeamsType;
use App\Repository\TeamRepository;
use App\Repository\TimesheetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * User profile controller
 *
 * @Route(path="/profile")
 * @Security("is_granted('view_own_profile') or is_granted('view_other_profile')")
 */
class ProfileController extends AbstractController
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;
    /**
     * @var TeamRepository
     */
    private $teams;

    public function __construct(UserPasswordEncoderInterface $encoder, EventDispatcherInterface $dispatcher, TeamRepository $teams)
    {
        $this->encoder = $encoder;
        $this->dispatcher = $dispatcher;
        $this->teams = $teams;
    }

    /**
     * @Route(path="/", name="my_profile", methods={"GET"})
     */
    public function profileAction()
    {
        return $this->redirectToRoute('user_profile', ['username' => $this->getUser()->getUsername()]);
    }

    /**
     * @Route(path="/{username}", name="user_profile", methods={"GET"})
     * @Security("is_granted('view', profile)")
     */
    public function indexAction(User $profile, TimesheetRepository $repository)
    {
        $userStats = $repository->getUserStatistics($profile);
        $monthlyStats = $repository->getMonthlyStats($profile);

        $viewVars = [
            'tab' => 'charts',
            'user' => $profile,
            'stats' => $userStats,
            'years' => $monthlyStats,
        ];

        return $this->render('user/stats.html.twig', $viewVars);
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

            return $this->redirectToRoute('user_profile_edit', ['username' => $profile->getUsername()]);
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

            return $this->redirectToRoute('user_profile_password', ['username' => $profile->getUsername()]);
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

            return $this->redirectToRoute('user_profile_api_token', ['username' => $profile->getUsername()]);
        }

        return $this->getProfileView($profile, 'api-token', null, null, null, $form);
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

            return $this->redirectToRoute('user_profile_roles', ['username' => $profile->getUsername()]);
        }

        return $this->getProfileView($profile, 'roles', null, null, $form);
    }

    /**
     * @Route(path="/{username}/teams", name="user_profile_teams", methods={"GET", "POST"})
     * @Security("is_granted('teams', profile)")
     */
    public function teamsAction(User $profile, Request $request)
    {
        $form = $this->createTeamsForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile_teams', ['username' => $profile->getUsername()]);
        }

        return $this->getProfileView($profile, 'teams', null, null, null, null, $form);
    }

    /**
     * @Route(path="/{username}/prefs", name="user_profile_preferences", methods={"GET", "POST"})
     * @Security("is_granted('preferences', profile)")
     */
    public function preferencesAction(User $profile, Request $request)
    {
        // we need to prepare the user preferences, which is done via an EventSubscriber
        $event = new PrepareUserEvent($profile);
        $this->dispatcher->dispatch($event);

        /** @var \ArrayIterator $iterator */
        $iterator = $profile->getPreferences()->getIterator();
        $iterator->uasort(function (UserPreference $a, UserPreference $b) {
            return ($a->getOrder() < $b->getOrder()) ? -1 : 1;
        });
        $profile->setPreferences(new ArrayCollection(iterator_to_array($iterator)));

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

            // switch locale ONLY if updated profile is the current user
            $locale = $request->getLocale();
            if ($this->getUser()->getId() === $profile->getId()) {
                $locale = $profile->getPreferenceValue('language', $locale);
            }

            return $this->redirectToRoute('user_profile_preferences', [
                '_locale' => $locale,
                'username' => $profile->getUsername()
            ]);
        }

        return $this->render('user/form.html.twig', [
            'tab' => 'preferences',
            'user' => $profile,
            'form' => $form->createView(),
        ]);
    }

    protected function getProfileView(
        User $user,
        string $tab,
        FormInterface $editForm = null,
        FormInterface $pwdForm = null,
        FormInterface $rolesForm = null,
        FormInterface $apiTokenForm = null,
        FormInterface $teamsForm = null
    ): Response {
        $forms = [];

        if ($this->isGranted('edit', $user)) {
            $editForm = $editForm ?: $this->createEditForm($user);
            $forms['settings'] = $editForm->createView();
        }
        if ($this->isGranted('password', $user)) {
            $pwdForm = $pwdForm ?: $this->createPasswordForm($user);
            $forms['password'] = $pwdForm->createView();
        }
        if ($this->isGranted('api-token', $user)) {
            $apiTokenForm = $apiTokenForm ?: $this->createApiTokenForm($user);
            $forms['api-token'] = $apiTokenForm->createView();
        }
        if ($this->isGranted('teams', $user) && $this->teams->count([]) > 0) {
            $teamsForm = $teamsForm ?: $this->createTeamsForm($user);
            $forms['teams'] = $teamsForm->createView();
        }
        if ($this->isGranted('roles', $user)) {
            $rolesForm = $rolesForm ?: $this->createRolesForm($user);
            $forms['roles'] = $rolesForm->createView();
        }

        return $this->render('user/profile.html.twig', [
            'tab' => $tab,
            'user' => $user,
            'forms' => $forms
        ]);
    }

    private function createPreferencesForm(User $user): FormInterface
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

    private function createEditForm(User $user): FormInterface
    {
        return $this->createForm(
            UserEditType::class,
            $user,
            [
                'action' => $this->generateUrl('user_profile_edit', ['username' => $user->getUsername()]),
                'method' => 'POST',
                'include_active_flag' => ($user->getId() !== $this->getUser()->getId()),
                'include_preferences' => false,
            ]
        );
    }

    private function createRolesForm(User $user): FormInterface
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

    private function createTeamsForm(User $user): FormInterface
    {
        return $this->createForm(
            UserTeamsType::class,
            $user,
            [
                'action' => $this->generateUrl('user_profile_teams', ['username' => $user->getUsername()]),
                'method' => 'POST',
            ]
        );
    }

    private function createPasswordForm(User $user): FormInterface
    {
        return $this->createForm(
            UserPasswordType::class,
            $user,
            [
                'validation_groups' => ['PasswordUpdate'],
                'action' => $this->generateUrl('user_profile_password', ['username' => $user->getUsername()]),
                'method' => 'POST'
            ]
        );
    }

    private function createApiTokenForm(User $user): FormInterface
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
