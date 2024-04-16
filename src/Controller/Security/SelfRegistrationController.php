<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Security;

use App\Configuration\SystemConfiguration;
use App\Controller\AbstractController;
use App\Entity\User;
use App\Event\EmailEvent;
use App\Event\EmailSelfRegistrationEvent;
use App\Form\SelfRegistrationForm;
use App\User\LoginManager;
use App\User\UserService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/register')]
final class SelfRegistrationController extends AbstractController
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private UserService $userService,
        private TokenStorageInterface $tokenStorage,
        private SystemConfiguration $configuration
    ) {
    }

    #[Route(path: '/', name: 'registration_register', methods: ['GET', 'POST'])]
    public function registerAction(Request $request, TranslatorInterface $translator): Response
    {
        if (!$this->configuration->isSelfRegistrationActive()) {
            throw $this->createNotFoundException();
        }

        $user = $this->userService->createNewUser();
        $user->setLanguage($request->getLocale());

        $form = $this->createSelfRegistrationForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setEnabled(false);
            $user->setConfirmationToken($this->userService->generateSecurityToken());

            $mail = $this->generateConfirmationEmail($user, $translator);
            $event = new EmailSelfRegistrationEvent($user, $mail);
            $this->eventDispatcher->dispatch($event);

            // this will finally send the email
            $this->eventDispatcher->dispatch(new EmailEvent($event->getEmail()));

            $request->getSession()->set('confirmation_email_address', $user->getEmail());

            $this->userService->saveUser($user);

            return $this->redirectToRoute('user_registration_check_email');
        }

        return $this->render('security/self-registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Tell the user to check their email provider.
     */
    #[Route(path: '/check-email', name: 'user_registration_check_email', methods: ['GET'])]
    public function checkEmailAction(Request $request): Response
    {
        if (!$this->configuration->isSelfRegistrationActive()) {
            throw $this->createNotFoundException();
        }

        $email = $request->getSession()->get('confirmation_email_address');

        if (empty($email)) {
            return $this->redirectToRoute('registration_register');
        }

        $request->getSession()->remove('confirmation_email_address');
        $user = $this->userService->findUserByEmail($email);

        if (null === $user) {
            return $this->redirectToRoute('login');
        }

        return $this->render('security/self-registration/check_email.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Receive the confirmation token from user email provider, login the user.
     */
    #[Route(path: '/confirm/{token}', name: 'registration_confirm', methods: ['GET'])]
    public function confirmAction(LoginManager $loginManager, ?string $token): Response
    {
        if (!$this->configuration->isSelfRegistrationActive()) {
            throw $this->createNotFoundException();
        }

        $user = $this->userService->findUserByConfirmationToken($token);

        if (null === $user) {
            return $this->redirectToRoute('login');
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);

        $this->userService->saveUser($user);

        $response = $this->redirectToRoute('registration_confirmed');
        $loginManager->logInUser($user, $response);

        return $response;
    }

    /**
     * Tell the user his account is now confirmed.
     */
    #[Route(path: '/confirmed', name: 'registration_confirmed', methods: ['GET'])]
    public function confirmedAction(Request $request): Response
    {
        if (!$this->configuration->isSelfRegistrationActive()) {
            throw $this->createNotFoundException();
        }

        return $this->render('security/self-registration/confirmed.html.twig', [
            'user' => $this->getUser(),
            'targetUrl' => $this->getTargetUrlFromSession($request->getSession()),
        ]);
    }

    private function createSelfRegistrationForm(): FormInterface
    {
        $options = ['validation_groups' => ['Registration', 'Default']];

        return $this->createFormBuilder()->create('user_registration_form', SelfRegistrationForm::class, $options)->getForm();
    }

    private function getTargetUrlFromSession(SessionInterface $session): ?string
    {
        $token = $this->tokenStorage->getToken();
        if ($token === null || !method_exists($token, 'getProviderKey')) {
            return null;
        }

        $key = sprintf('_security.%s.target_path', $token->getProviderKey());

        if ($session->has($key)) {
            return $session->get($key);
        }

        return null;
    }

    private function generateConfirmationEmail(User $user, TranslatorInterface $translator): Email
    {
        $username = $user->getDisplayName();
        $language = $user->getLanguage();

        $url = $this->generateUrl('registration_confirm', ['token' => $user->getConfirmationToken()], UrlGeneratorInterface::ABSOLUTE_URL);

        return (new TemplatedEmail())
            ->to(new Address($user->getEmail()))
            ->subject(
                $translator->trans('registration.subject', ['%username%' => $username], 'email', $language)
            )
            ->htmlTemplate('emails/confirmation.html.twig')
            ->context([
                'user' => $user,
                'username' => $username,
                'confirmationUrl' => $url,
            ])
        ;
    }
}
