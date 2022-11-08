<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\LanguageType;
use App\Form\Type\TimezoneType;
use App\User\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/wizard')]
#[Security("is_granted('IS_AUTHENTICATED_FULLY')")]
final class WizardController extends AbstractController
{
    #[Route(path: '/{wizard}', name: 'wizard', methods: ['GET', 'POST'])]
    #[Security("is_granted('view_own_timesheet')")]
    public function wizard(Request $request, UserService $userService, string $wizard): Response
    {
        $user = $this->getUser();

        if ($wizard === 'intro') {
            $user->setWizardAsSeen('intro');
            $userService->updateUser($user);

            return $this->render('wizard/intro.html.twig', [
                'percent' => 0,
                'next' => 'profile',
            ]);
        }

        if ($wizard === 'profile') {
            $data = [
                'language' => $request->getLocale(),
                'timezone' => $user->getTimezone(),
            ];

            $form = $this->createFormBuilder($data)
                ->add('language', LanguageType::class)
                ->add('timezone', TimezoneType::class)
                ->setAction($this->generateUrl('wizard', ['wizard' => 'profile']))
                ->setMethod('POST')
                ->getForm();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $user->setLanguage($data['language']);
                $user->setTimezone($data['timezone']);
                $user->setWizardAsSeen('profile');
                $userService->updateUser($user);

                return $this->redirectToRoute('wizard', ['wizard' => 'done', '_locale' => $data['language']]);
            }

            return $this->render('wizard/profile.html.twig', [
                'percent' => \intval(100 / \count(User::WIZARDS) * 1),
                'previous' => 'intro',
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
