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
use App\User\UserService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/resetting')]
final class PasswordResetController extends AbstractController
{
    public const CSRF_TOKEN = 'password_reset';

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserService $userService,
        private readonly SystemConfiguration $configuration
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

        if ($this->isGranted('IS_AUTHENTICATED')) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('security/password-reset/request.html.twig');
    }

    /**
     * Request reset user password: submit form and send email.
     */
    #[Route(path: '/send-email', name: 'resetting_send_email', methods: ['POST'])]
    public function sendEmailAction(
        Request $request,
        TranslatorInterface $translator,
        CsrfTokenManagerInterface $csrfTokenManager,
        LoginLinkHandlerInterface $loginLinkHandler,
        RateLimiterFactory $resetPasswordLimiter
    ): Response
    {
        if (!$this->configuration->isPasswordResetActive()) {
            throw $this->createNotFoundException();
        }

        if ($this->isGranted('IS_AUTHENTICATED')) {
            return $this->redirectToRoute('homepage');
        }

        $limiter = $resetPasswordLimiter->create($request->getClientIp());
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            return new Response(null, Response::HTTP_TOO_MANY_REQUESTS);
        }

        $username = $request->request->get('username');
        $token = $request->request->get('_csrf_token');

        try {
            $user = null;

            if (\is_string($token) && $csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN, $token))) {
                if (\is_string($username) && $username !== '') {
                    $user = $this->userService->findUserByUsernameOrEmail($username);
                }
            }

            $csrfTokenManager->refreshToken(self::CSRF_TOKEN);

            // do not leak the information that this user is not registered OR cannot use this type of login
            if ($user === null || !$user->isInternalUser()) {
                return $this->redirectToRoute('resetting_check_email');
            }

            if (!$user->isPasswordRequestNonExpired($this->configuration->getPasswordResetRetryLifetime())) {
                $loginLinkDetails = $loginLinkHandler->createLoginLink($user, $request, $this->configuration->getPasswordResetRetryLifetime()); // @phpstan-ignore-line
                $loginLink = $loginLinkDetails->getUrl();

                $mail = $this->generateResettingEmailMessage($user, $translator, $loginLink);
                $event = new EmailPasswordResetEvent($user, $mail);
                $this->eventDispatcher->dispatch($event);

                // this will send the email
                $this->eventDispatcher->dispatch(new EmailEvent($event->getEmail()));

                $user->markPasswordRequested();
                $user->setRequiresPasswordReset(true);
                $this->userService->saveUser($user);
            }
        } catch (\Exception $ex) {
            // this is an expected exception: do not log this attempt
        }

        return $this->redirectToRoute('resetting_check_email');
    }

    /**
     * Tell the user to check his emails.
     */
    #[Route(path: '/check-email', name: 'resetting_check_email', methods: ['GET'])]
    public function checkEmailAction(): Response
    {
        if (!$this->configuration->isPasswordResetActive()) {
            throw $this->createNotFoundException();
        }

        return $this->render('security/password-reset/check_email.html.twig', [
            'tokenLifetime' => $this->configuration->getPasswordResetRetryLifetime(),
        ]);
    }

    private function generateResettingEmailMessage(User $user, TranslatorInterface $translator, string $url): Email
    {
        $username = $user->getDisplayName();
        $language = $user->getLanguage();

        return (new TemplatedEmail())
            ->locale($language)
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
