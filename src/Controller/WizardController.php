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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/wizard')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class WizardController extends AbstractController
{
    #[Route(path: '/{wizard}', name: 'wizard', methods: ['GET', 'POST'])]
    public function wizard(Request $request, UserService $userService, string $wizard): Response
    {
        $user = $this->getUser();

        if ($wizard === 'intro') {
            $user->setWizardAsSeen('intro');
            $userService->saveUser($user);

            return $this->render('wizard/intro.html.twig', [
                'percent' => 0,
                'next' => 'profile',
            ]);
        }

        if ($wizard === 'profile') {
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
                ->setAction($this->generateUrl('wizard', ['wizard' => 'profile']))
                ->setMethod('POST')
                ->getForm();

            $next = 'done';
            if ($user->requiresPasswordReset()) {
                $next = 'password';
            }

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
                    return $this->redirectToRoute('wizard', ['wizard' => 'profile', '_locale' => $user->getLanguage()]);
                } else {
                    return $this->redirectToRoute('wizard', ['wizard' => $next, '_locale' => $user->getLanguage()]);
                }
            }

            return $this->render('wizard/profile.html.twig', [
                'percent' => \intval(100 / \count(User::WIZARDS) * 1),
                'previous' => 'intro',
                'next' => $next,
                'form' => $form->createView(),
            ]);
        }

        if ($wizard === 'password' || $user->requiresPasswordReset()) {
            $form = $this->createForm(UserPasswordType::class, $user, [
                'action' => $this->generateUrl('wizard', ['wizard' => 'password']),
                'method' => 'POST',
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user->setRequiresPasswordReset(false);
                $userService->saveUser($user);

                return $this->redirectToRoute('wizard', ['wizard' => 'done']);
            }

            $previous = 'profile';
            $percent = \intval(100 / \count(User::WIZARDS) * 1);

            if ($user->requiresPasswordReset()) {
                $previous = null;
                $percent = null;
            }

            return $this->render('wizard/password.html.twig', [
                'percent' => $percent,
                'previous' => $previous,
                'next' => 'done',
                'form' => $form->createView(),
            ]);
        }

        // this is a virtual step that is not registered as wizard, but instead should be shown every time
        // a new wizard is introduced: so we do not register it as "seen"
        if ($wizard === 'done') {
            return $this->render('wizard/done.html.twig', [
                'percent' => 100,
                'previous' => 'profile',
            ]);
        }

        throw $this->createNotFoundException('Unknown wizard');
    }
}
