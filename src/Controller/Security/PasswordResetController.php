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
use App\Event\EmailPasswordResetEvent;
use App\Form\PasswordResetForm;
use App\User\LoginManager;
use App\User\UserService;
use DateTime;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/resetting')]
final class PasswordResetController extends AbstractController
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private UserService $userService,
        private SystemConfiguration $configuration
    ) {
    }

    /**
     * Request reset user password: show form.
     */
    #[Route(path: '/request', name: 'resetting_request', methods: ['GET'])]
    public function requestAction(): Response
    {
        if (!$this->configuration->isPasswordResetActive()) {
            throw $this->createNotFoundException();
        }

        return $this->render('security/password-reset/request.html.twig');
    }

    /**
     * Request reset user password: submit form and send email.
     */
    #[Route(path: '/send-email', name: 'resetting_send_email', methods: ['POST'])]
    public function sendEmailAction(Request $request, TranslatorInterface $translator): Response
    {
        if (!$this->configuration->isPasswordResetActive()) {
            throw $this->createNotFoundException();
        }

        $username = $request->request->get('username');
        $user = $this->userService->findUserByUsernameOrEmail($username);

        if (null !== $user && !$user->isPasswordRequestNonExpired($this->configuration->getPasswordResetRetryLifetime())) {
            if (!$user->isInternalUser()) {
                throw $this->createAccessDeniedException(
                    sprintf('The user "%s" tried to reset the password, but it is registered as "%s" auth-type.', $user->getUserIdentifier(), $user->getAuth())
                );
            }

            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken($this->userService->generateSecurityToken());
            }

            $mail = $this->generateResettingEmailMessage($user, $translator);
            $event = new EmailPasswordResetEvent($user, $mail);
            $this->eventDispatcher->dispatch($event);

            // this will finally send the email
            $this->eventDispatcher->dispatch(new EmailEvent($event->getEmail()));

            $user->setPasswordRequestedAt(new DateTime());
            $this->userService->updateUser($user);
        }

        return $this->redirectToRoute('resetting_check_email', ['username' => $username]);
    }

    /**
     * Tell the user to check his email provider.
     */
    #[Route(path: '/check-email', name: 'resetting_check_email', methods: ['GET'])]
    public function checkEmailAction(Request $request): Response
    {
        if (!$this->configuration->isPasswordResetActive()) {
            throw $this->createNotFoundException();
        }

        $username = $request->query->get('username');

        if (empty($username)) {
            // the user does not come from the sendEmail action
            return $this->redirectToRoute('resetting_request');
        }

        return $this->render('security/password-reset/check_email.html.twig', [
            'tokenLifetime' => ceil($this->configuration->getPasswordResetRetryLifetime() / 3600),
        ]);
    }

    /**
     * Reset user password.
     */
    #[Route(path: '/reset/{token}', name: 'resetting_reset', methods: ['GET', 'POST'])]
    public function resetAction(Request $request, LoginManager $loginManager, ?string $token): Response
    {
        if (!$this->configuration->isPasswordResetActive()) {
            throw $this->createNotFoundException();
        }

        $user = $this->userService->findUserByConfirmationToken($token);

        if (null === $user) {
            return $this->redirectToRoute('login');
        }

        if (!$user->isPasswordRequestNonExpired($this->configuration->getPasswordResetTokenLifetime())) {
            return $this->redirectToRoute('resetting_request');
        }

        $form = $this->createResetForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);
            $user->setEnabled(true);

            $this->userService->updateUser($user);

            $response = $this->redirectToRoute('my_profile');
            $loginManager->logInUser($user, $response);

            return $response;
        }

        return $this->render('security/password-reset/reset.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }

    private function createResetForm(): FormInterface
    {
        $options = ['validation_groups' => ['ResetPassword', 'Default']];

        return $this->createFormBuilder()->create('resetting_form', PasswordResetForm::class, $options)->getForm();
    }

    private function generateResettingEmailMessage(User $user, TranslatorInterface $translator): Email
    {
        $username = $user->getDisplayName();
        $language = $user->getLanguage();

        $url = $this->generateUrl('resetting_reset', ['token' => $user->getConfirmationToken()], UrlGeneratorInterface::ABSOLUTE_URL);

        return (new TemplatedEmail())
            ->to(new Address($user->getEmail()))
            ->subject(
                $translator->trans('reset.subject', ['%username%' => $username], 'email', $language)
            )
            ->htmlTemplate('emails/password-reset.html.twig')
            ->context([
                'user' => $user,
                'username' => $username,
                'confirmationUrl' => $url,
            ])
        ;
    }
}
