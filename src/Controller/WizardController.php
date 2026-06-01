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
use App\Form\Type\SkinType;
use App\Form\Type\TimezoneType;
use App\Form\Type\UserLanguageType;
use App\Form\Type\UserLocaleType;
use App\Form\UserPasswordType;
use App\User\UserService;
use App\Wizard\WizardManager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/wizard')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class WizardController extends AbstractController
{
    /**
     * Virtual "forward" route. Redirects to the first step the user has not
     * seen yet, or to the finish page if all steps are done.
     *
     * The WizardSubscriber already intercepts this route on kernel.request,
     * but the action is kept as a defensive fallback (the subscriber returns
     * early when all steps are seen).
     */
    #[Route(path: '/next/', name: 'wizard_next', methods: ['GET'])]
    public function next(WizardManager $wizardManager): Response
    {
        $user = $this->getUser();
        $step = $wizardManager->getFirstUnseenStep($user);

        if ($step !== null) {
            return $this->redirectToRoute($step->route);
        }

        return $this->redirectToRoute('wizard_finish');
    }

    /**
     * Virtual "backward" route. The caller passes its own step id as
     * {@code from} so that the previous step can be resolved without the
     * caller needing to know who comes before it.
     */
    #[Route(path: '/previous/{from}', name: 'wizard_previous', requirements: ['from' => '[a-zA-Z0-9_-]+'], methods: ['GET'])]
    public function previous(string $from, WizardManager $wizardManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $step = $wizardManager->getPreviousStep($user, $from);

        if ($step !== null) {
            return $this->redirectToRoute($step->route);
        }

        return $this->redirectToRoute('wizard_intro');
    }

    #[Route(path: '/intro', name: 'wizard_intro', methods: ['GET'])]
    public function intro(UserService $userService, WizardManager $wizardManager): Response
    {
        $user = $this->getUser();

        $user->setWizardAsSeen('intro');
        $userService->saveUser($user);

        return $this->render(
            'wizard/intro.html.twig',
            $wizardManager->getNavigation($user, 'intro')
        );
    }

    #[Route(path: '/profile', name: 'wizard_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, UserService $userService, WizardManager $wizardManager): Response
    {
        $user = $this->getUser();

        $data = [
            UserPreference::LANGUAGE => $user->getPreferenceValue(UserPreference::LANGUAGE, $request->getLocale(), false),
            UserPreference::LOCALE => $user->getPreferenceValue(UserPreference::LOCALE, $request->getLocale(), false),
            UserPreference::TIMEZONE => $user->getTimezone(),
            UserPreference::SKIN => $user->getSkin(),
            'reload' => '0',
        ];

        $form = $this->createFormBuilder($data)
            ->add(UserPreference::LANGUAGE, UserLanguageType::class)
            ->add(UserPreference::LOCALE, UserLocaleType::class, ['help' => null])
            ->add(UserPreference::TIMEZONE, TimezoneType::class)
            ->add(UserPreference::SKIN, SkinType::class)
            ->add('reload', HiddenType::class)
            ->setAction($this->generateUrl('wizard_profile'))
            ->setMethod('POST')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, string> $data */
            $data = $form->getData();
            $user->setLanguage($data[UserPreference::LANGUAGE]);
            $user->setLocale($data[UserPreference::LOCALE]);
            $user->setTimezone($data[UserPreference::TIMEZONE]);
            $user->setPreferenceValue(UserPreference::SKIN, $data[UserPreference::SKIN]);
            $user->setWizardAsSeen('profile');
            $userService->saveUser($user);

            if ($data['reload'] === '1') {
                return $this->redirectToRoute('wizard_profile', ['_locale' => $user->getLanguage()]);
            }

            // Delegate "what comes next" to the WizardManager so optional steps
            // (e.g. the password step, or plugin-contributed steps) are picked
            // up automatically.
            return $this->redirectToRoute('wizard_next', ['_locale' => $user->getLanguage()]);
        }

        return $this->render(
            'wizard/profile.html.twig',
            array_merge(
                ['form' => $form->createView()],
                $wizardManager->getNavigation($user, 'profile')
            )
        );
    }

    #[Route(path: '/password', name: 'wizard_password', methods: ['GET', 'POST'])]
    public function password(Request $request, UserService $userService, WizardManager $wizardManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserPasswordType::class, $user, [
            'action' => $this->generateUrl('wizard_password'),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRequiresPasswordReset(false);
            // do not set wizard as seen, as password reset utilizes the wizard framework but lives outside its flow
            $userService->saveUser($user);

            return $this->redirectToRoute('wizard_next');
        }

        // this has no wizard navigation, as it lives outside the normal wizard flow
        return $this->render('wizard/password.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Virtual finish step. Not registered as a wizard step, so it is shown
     * every time a new wizard step is introduced and never marked as "seen".
     */
    #[Route(path: '/finish', name: 'wizard_finish', methods: ['GET'])]
    public function finish(WizardManager $wizardManager): Response
    {
        $user = $this->getUser();

        // Previous link points back to the last registered step in the sequence.
        $previousStep = null;
        foreach ($wizardManager->getSteps($user) as $step) {
            $previousStep = $step;
        }

        return $this->render('wizard/done.html.twig', [
            'previous' => $previousStep?->route,
        ]);
    }
}
