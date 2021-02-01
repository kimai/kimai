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
use App\Utils\LocaleSettings;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * User profile controller
 *
 * @Route(path="/profile")
 * @Security("is_granted('view_own_profile') or is_granted('view_other_profile')")
 */
final class ProfileController extends AbstractController
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

    public function __construct(UserPasswordEncoderInterface $encoder, EventDispatcherInterface $dispatcher)
    {
        $this->encoder = $encoder;
        $this->dispatcher = $dispatcher;
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
    public function indexAction(User $profile, TimesheetRepository $repository, LocaleSettings $localeSettings)
    {
        $userStats = $repository->getUserStatistics($profile);
        $monthlyStats = $repository->getMonthlyStats($profile);

        $viewVars = [
            'tab' => 'charts',
            'user' => $profile,
            'stats' => $userStats,
            'years' => $monthlyStats,
            'stat_date_format' => $localeSettings->getDatePickerFormat(),
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

        return $this->render('user/profile.html.twig', [
            'tab' => 'settings',
            'user' => $profile,
            'form' => $form->createView(),
        ]);
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

        return $this->render('user/profile.html.twig', [
            'tab' => 'password',
            'user' => $profile,
            'form' => $form->createView(),
        ]);
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

        return $this->render('user/api-token.html.twig', [
            'tab' => 'api-token',
            'user' => $profile,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(path="/{username}/roles", name="user_profile_roles", methods={"GET", "POST"})
     * @Security("is_granted('roles', profile)")
     */
    public function rolesAction(User $profile, Request $request)
    {
        $isSuperAdmin = $profile->isSuperAdmin();

        $form = $this->createRolesForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // fix that a super admin cannot remove this role from himself.
            // would be a massive problem, in case that there is only one super-admin account existing
            if ($isSuperAdmin && !$profile->isSuperAdmin() && $profile->getId() === $this->getUser()->getId()) {
                $profile->setSuperAdmin(true);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile_roles', ['username' => $profile->getUsername()]);
        }

        return $this->render('user/profile.html.twig', [
            'tab' => 'roles',
            'user' => $profile,
            'form' => $form->createView(),
        ]);
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

        return $this->render('user/profile.html.twig', [
            'tab' => 'teams',
            'user' => $profile,
            'form' => $form->createView(),
        ]);
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

        $original = [];
        foreach ($profile->getPreferences() as $preference) {
            $original[$preference->getName()] = $preference;
        }

        $form = $this->createPreferencesForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
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
            } else {
                $this->flashError('action.update.error', ['%reason%' => 'Validation failed']);
            }
        }

        // prepare ordered preferences
        $sections = [];

        /** @var \ArrayIterator $iterator */
        $iterator = $profile->getPreferences()->getIterator();
        $iterator->uasort(function (UserPreference $a, UserPreference $b) {
            return ($a->getOrder() < $b->getOrder()) ? -1 : 1;
        });

        /** @var UserPreference $pref */
        foreach ($iterator as $pref) {
            if ($pref->isEnabled()) {
                $sections[$pref->getSection()] = $pref->getSection();
            }
        }

        return $this->render('user/form.html.twig', [
            'tab' => 'preferences',
            'user' => $profile,
            'form' => $form->createView(),
            'sections' => $sections
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
                'action' => $this->generateUrl('user_profile_api_token', ['username' => $user->getUsername()]),
                'method' => 'POST'
            ]
        );
    }
}
