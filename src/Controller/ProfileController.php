<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Event\PrepareUserEvent;
use App\Form\AccessTokenForm;
use App\Form\Model\TotpActivation;
use App\Form\UserApiPasswordType;
use App\Form\UserContractType;
use App\Form\UserEditType;
use App\Form\UserPasswordType;
use App\Form\UserPreferencesForm;
use App\Form\UserRolesType;
use App\Form\UserTeamsType;
use App\Form\UserTwoFactorType;
use App\Repository\AccessTokenRepository;
use App\Repository\Query\TimesheetStatisticQuery;
use App\Repository\TeamRepository;
use App\Repository\TimesheetRepository;
use App\Repository\UserRepository;
use App\Timesheet\TimesheetStatisticService;
use App\User\UserService;
use App\Utils\PageSetup;
use Doctrine\Common\Collections\ArrayCollection;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * User profile controller
 */
#[Route(path: '/profile')]
#[IsGranted(new Expression("is_granted('view_own_profile') or is_granted('view_other_profile')"))]
final class ProfileController extends AbstractController
{
    #[Route(path: '/', name: 'my_profile', methods: ['GET'])]
    public function profileAction(): Response
    {
        return $this->redirectToRoute('user_profile', ['username' => $this->getUser()->getUserIdentifier()]);
    }

    private function getPageSetup(User $profile, string $view): PageSetup
    {
        $page = new PageSetup('users');
        $page->setHelp('users.html');
        $page->setActionName('user');
        $page->setActionView($view);
        $page->setActionPayload(['user' => $profile]);

        return $page;
    }

    #[Route(path: '/{username}', name: 'user_profile', methods: ['GET'])]
    #[IsGranted('view', 'profile')]
    public function indexAction(User $profile, TimesheetRepository $repository, TimesheetStatisticService $statisticService): Response
    {
        $dateFactory = $this->getDateTimeFactory();
        $userStats = $repository->getUserStatistics($profile);
        $workStartingDay = $profile->getWorkStartingDay();
        if ($workStartingDay === null) {
            $workStartingDay = $statisticService->findFirstRecordDate($profile);
        }

        $begin = $workStartingDay ?? $dateFactory->getStartOfMonth();
        $end = $dateFactory->getEndOfMonth();

        // statistic service does not fill up the complete year by default!
        // but we need a full year, because the chart needs always 12 month
        $begin = $dateFactory->createStartOfYear($begin);

        $query = new TimesheetStatisticQuery($begin, $end, [$profile]);

        $viewVars = [
            'tab' => 'charts',
            'page_setup' => $this->getPageSetup($profile, 'charts'),
            'user' => $profile,
            'stats' => $userStats,
            'workingSince' => $workStartingDay,
            'workMonths' => $statisticService->getMonthlyStats($query)[0]
        ];

        return $this->render('user/stats.html.twig', $viewVars);
    }

    #[Route(path: '/{username}/edit', name: 'user_profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit', 'profile')]
    public function editAction(User $profile, Request $request, UserRepository $userRepository): Response
    {
        $form = $this->createEditForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->saveUser($profile);

            $this->flashSuccess('action.update.success');

            $locale = $request->getLocale();
            if ($this->getUser()->getId() === $profile->getId()) {
                $locale = $profile->getPreferenceValue('language', $locale, false);
            }

            return $this->redirectToRoute('user_profile_edit', ['username' => $profile->getUserIdentifier(), '_locale' => $locale]);
        }

        return $this->render('user/profile.html.twig', [
            'tab' => 'edit',
            'page_setup' => $this->getPageSetup($profile, 'edit'),
            'user' => $profile,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{username}/password', name: 'user_profile_password', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted('password', 'profile')]
    public function passwordAction(User $profile, Request $request, UserService $userService): Response
    {
        $form = $this->createPasswordForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userService->saveUser($profile);

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile_password', ['username' => $profile->getUserIdentifier()]);
        }

        return $this->render('user/form.html.twig', [
            'tab' => 'password',
            'page_setup' => $this->getPageSetup($profile, 'password'),
            'user' => $profile,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{username}/create-access-token', name: 'user_profile_access_token', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted('api-token', 'profile')]
    public function createAccessToken(User $profile, Request $request, AccessTokenRepository $accessTokenRepository): Response
    {
        $accessToken = new AccessToken($profile, substr(bin2hex(random_bytes(100)), 0, 25));

        $form = $this->createForm(AccessTokenForm::class, $accessToken, [
            'action' => $this->generateUrl('user_profile_access_token', ['username' => $profile->getUserIdentifier()]),
            'method' => 'POST'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $accessTokenRepository->saveAccessToken($accessToken);

            $this->flashSuccess('action.update.success');
            $request->getSession()->set('_show_access_token', $accessToken->getId());

            return $this->redirectToRoute('user_profile_api_token', ['username' => $profile->getUserIdentifier(), 'hide-token' => '1']);
        }

        return $this->render('user/access-token.html.twig', [
            'access_token' => $accessToken,
            'user' => $profile,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{username}/api-token', name: 'user_profile_api_token', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted('api-token', 'profile')]
    public function apiTokenAction(User $profile, Request $request, UserService $userService, AccessTokenRepository $accessTokenRepository): Response
    {
        $form = $this->createForm(UserApiPasswordType::class, $profile, [
            'action' => $this->generateUrl('user_profile_api_token', ['username' => $profile->getUserIdentifier()]),
            'method' => 'POST'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            @trigger_error('User ' . $profile->getUsername() . ' created deprecated API password.', E_USER_DEPRECATED);

            $userService->saveUser($profile);

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile_api_token', ['username' => $profile->getUserIdentifier()]);
        }

        $accessTokens = $accessTokenRepository->findForUser($profile);

        $createdToken = null;

        if (!$request->query->has('hide-token')) {
            $createdId = $request->getSession()->get('_show_access_token');
            $request->getSession()->remove('_show_access_token');
            if ($createdId !== null) {
                foreach ($accessTokens as $accessToken) {
                    if ($accessToken->getId() === $createdId) {
                        $createdToken = $accessToken;
                    }
                }
            }
        }

        return $this->render('user/api-token.html.twig', [
            'tab' => 'api-token',
            'created_token' => $createdToken,
            'access_tokens' => $accessTokens,
            'page_setup' => $this->getPageSetup($profile, 'api-token'),
            'user' => $profile,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{username}/roles', name: 'user_profile_roles', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted('roles', 'profile')]
    public function rolesAction(User $profile, Request $request, UserRepository $userRepository): Response
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

            $userRepository->saveUser($profile);

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile_roles', ['username' => $profile->getUserIdentifier()]);
        }

        return $this->render('user/form.html.twig', [
            'tab' => 'roles',
            'page_setup' => $this->getPageSetup($profile, 'roles'),
            'user' => $profile,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{username}/contract', name: 'user_profile_contract', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted('contract', 'profile')]
    public function contractAction(User $profile, Request $request, UserRepository $userRepository): Response
    {
        $form = $this->createForm(UserContractType::class, $profile, [
            'action' => $this->generateUrl('user_profile_contract', ['username' => $profile->getUserIdentifier()]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->saveUser($profile);
            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile_contract', ['username' => $profile->getUserIdentifier()]);
        }

        return $this->render('user/contract.html.twig', [
            'tab' => 'contract',
            'page_setup' => $this->getPageSetup($profile, 'contract'),
            'user' => $profile,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{username}/teams', name: 'user_profile_teams', methods: ['GET', 'POST'])]
    #[IsGranted('teams', 'profile')]
    public function teamsAction(User $profile, Request $request, UserRepository $userRepository, TeamRepository $teamRepository): Response
    {
        $originalMembers = new ArrayCollection();
        foreach ($profile->getMemberships() as $member) {
            $originalMembers->add($member);
        }

        $form = $this->createTeamsForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($originalMembers as $member) {
                if (!$profile->hasMembership($member)) {
                    $member->setTeam(null);
                    $member->setUser(null);
                    $teamRepository->removeTeamMember($member);
                }
            }

            $userRepository->saveUser($profile);

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile_teams', ['username' => $profile->getUserIdentifier()]);
        }

        return $this->render('user/form.html.twig', [
            'tab' => 'teams',
            'page_setup' => $this->getPageSetup($profile, 'teams'),
            'user' => $profile,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{username}/prefs', name: 'user_profile_preferences', methods: ['GET', 'POST'])]
    #[IsGranted('preferences', 'profile')]
    public function preferencesAction(User $profile, Request $request, EventDispatcherInterface $dispatcher, UserRepository $userRepository): Response
    {
        // we need to prepare the user preferences, which is done via an EventSubscriber
        $event = new PrepareUserEvent($profile, false);
        $dispatcher->dispatch($event);

        $form = $this->createPreferencesForm($profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->saveUser($profile);

            $this->flashSuccess('action.update.success');

            // switch locale ONLY if updated profile is the current user
            $locale = $request->getLocale();
            if ($this->getUser()->getId() === $profile->getId()) {
                $locale = $profile->getPreferenceValue('language', $locale, false);
            }

            return $this->redirectToRoute('user_profile_preferences', [
                '_locale' => $locale,
                'username' => $profile->getUserIdentifier()
            ]);
        }

        // prepare ordered preferences
        $sections = [];

        /** @var \ArrayIterator<int, UserPreference> $iterator */
        $iterator = $profile->getPreferences()->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getOrder() < $b->getOrder()) ? -1 : 1;
        });

        /** @var UserPreference $pref */
        foreach ($iterator as $pref) {
            if ($pref->isEnabled()) {
                $sections[$pref->getSection()] = $pref->getSection();
            }
        }

        return $this->render('user/preferences.html.twig', [
            'tab' => 'settings',
            'page_setup' => $this->getPageSetup($profile, 'settings'),
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
                'action' => $this->generateUrl('user_profile_preferences', ['username' => $user->getUserIdentifier()]),
                'method' => 'POST'
            ]
        );
    }

    private function createEditForm(User $user): FormInterface
    {
        $currentUser = $this->getUser();

        return $this->createForm(
            UserEditType::class,
            $user,
            [
                'action' => $this->generateUrl('user_profile_edit', ['username' => $user->getUserIdentifier()]),
                'method' => 'POST',
                'include_active_flag' => $user !== $currentUser,
                'include_preferences' => true,
                'include_supervisor' => $this->isGranted('supervisor', $user),
                'include_username' => $currentUser->isSuperAdmin() && $currentUser !== $user,
                'include_password_reset' => $this->isGranted('password', $user),
            ]
        );
    }

    private function createRolesForm(User $user): FormInterface
    {
        return $this->createForm(
            UserRolesType::class,
            $user,
            [
                'action' => $this->generateUrl('user_profile_roles', ['username' => $user->getUserIdentifier()]),
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
                'action' => $this->generateUrl('user_profile_teams', ['username' => $user->getUserIdentifier()]),
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
                'action' => $this->generateUrl('user_profile_password', ['username' => $user->getUserIdentifier()]),
                'method' => 'POST'
            ]
        );
    }

    #[Route(path: '/{username}/2fa', name: 'user_profile_2fa', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted('2fa', 'profile')]
    public function twoFactorAction(User $profile, Request $request, UserService $userService, TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        if (!$profile->hasTotpSecret()) {
            $profile->setTotpSecret($totpAuthenticator->generateSecret());
            $userService->saveUser($profile);
        }

        $data = new TotpActivation($profile);

        $form = $this->createForm(UserTwoFactorType::class, $data, [
            'action' => $this->generateUrl('user_profile_2fa', ['username' => $profile->getUserIdentifier()]),
            'method' => 'POST'
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profile->enableTotpAuthentication();
            $userService->saveUser($profile);

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile_2fa', ['username' => $profile->getUserIdentifier()]);
        }

        $qrCodeContent = $totpAuthenticator->getQRContent($profile);

        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($qrCodeContent)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(200)
            ->margin(0)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();

        return $this->render('user/2fa.html.twig', [
            'tab' => '2fa',
            'page_setup' => $this->getPageSetup($profile, '2fa'),
            'user' => $profile,
            'form' => $form->createView(),
            'deactivate' => $this->getTwoFactorDeactivationForm($profile)->createView(),
            'qr_code' => $result,
            'secret' => $profile->getTotpSecret(),
        ]);
    }

    private function getTwoFactorDeactivationForm(User $user): FormInterface
    {
        return $this->createFormBuilder([], [
            'action' => $this->generateUrl('user_profile_2fa_deactivate', ['username' => $user->getUserIdentifier()]),
            'method' => 'POST'
        ])->getForm();
    }

    #[Route(path: '/{username}/2fa_deactivate', name: 'user_profile_2fa_deactivate', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted('2fa', 'profile')]
    public function deactivateTwoFactorAction(User $profile, Request $request, UserService $userService, TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        if ($profile->hasTotpSecret()) {
            $form = $this->getTwoFactorDeactivationForm($profile);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $profile->disableTotpAuthentication();
                $userService->saveUser($profile);

                $this->flashSuccess('action.update.success');
            }
        }

        return $this->redirectToRoute('user_profile_2fa', ['username' => $profile->getUserIdentifier()]);
    }
}
