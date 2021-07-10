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
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route(path="/register")
 */
class SelfRegistrationController extends AbstractController
{
    private $eventDispatcher;
    private $userService;
    private $tokenStorage;
    private $configuration;

    public function __construct(EventDispatcherInterface $eventDispatcher, UserService $userService, TokenStorageInterface $tokenStorage, SystemConfiguration $configuration)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->userService = $userService;
        $this->tokenStorage = $tokenStorage;
        $this->configuration = $configuration;
    }

    /**
     * @Route(path="/", name="fos_user_registration_register", methods={"GET", "POST"})
     */
    public function registerAction(Request $request): Response
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

            $mail = $this->generateConfirmationEmail($user);
            $event = new EmailSelfRegistrationEvent($user, $mail);
            $this->eventDispatcher->dispatch($event);

            // this will finally send the email
            $this->eventDispatcher->dispatch(new EmailEvent($event->getEmail()));

            $request->getSession()->set('fos_user_send_confirmation_email/email', $user->getEmail());

            $this->userService->saveNewUser($user);

            return $this->redirectToRoute('fos_user_registration_check_email');
        }

        return $this->render('security/self-registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Tell the user to check their email provider.
     *
     * @Route(path="/check-email", name="fos_user_registration_check_email", methods={"GET"})
     */
    public function checkEmailAction(Request $request): Response
    {
        if (!$this->configuration->isSelfRegistrationActive()) {
            throw $this->createNotFoundException();
        }

        $email = $request->getSession()->get('fos_user_send_confirmation_email/email');

        if (empty($email)) {
            return $this->redirectToRoute('fos_user_registration_register');
        }

        $request->getSession()->remove('fos_user_send_confirmation_email/email');
        $user = $this->userService->findUserByEmail($email);

        if (null === $user) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        return $this->render('security/self-registration/check_email.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Receive the confirmation token from user email provider, login the user.
     *
     * @Route(path="/confirm/{token}", name="fos_user_registration_confirm", methods={"GET"})
     */
    public function confirmAction(LoginManager $loginManager, ?string $token): Response
    {
        if (!$this->configuration->isSelfRegistrationActive()) {
            throw $this->createNotFoundException();
        }

        $user = $this->userService->findUserByConfirmationToken($token);

        if (null === $user) {
            return $this->redirectToRoute('fos_user_security_login');
        }

        $user->setConfirmationToken(null);
        $user->setEnabled(true);

        $this->userService->updateUser($user);

        $response = $this->redirectToRoute('fos_user_registration_confirmed');
        $loginManager->logInUser($user, $response);

        return $response;
    }

    /**
     * Tell the user his account is now confirmed.
     *
     * @Route(path="/confirmed", name="fos_user_registration_confirmed", methods={"GET"})
     */
    public function confirmedAction(Request $request): Response
    {
        if (!$this->configuration->isSelfRegistrationActive()) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        if ($user === null) {
            throw $this->createAccessDeniedException('This user does not have access to this section.');
        }

        return $this->render('security/self-registration/confirmed.html.twig', [
            'user' => $user,
            'targetUrl' => $this->getTargetUrlFromSession($request->getSession()),
        ]);
    }

    private function createSelfRegistrationForm(): FormInterface
    {
        $options = ['validation_groups' => ['Registration', 'Default']];

        return $this->createFormBuilder()->create('fos_user_registration_form', SelfRegistrationForm::class, $options)->getForm();
    }

    private function getTargetUrlFromSession(SessionInterface $session): ?string
    {
        $token = $this->tokenStorage->getToken();
        if (!method_exists($token, 'getProviderKey')) {
            return null;
        }

        $key = sprintf('_security.%s.target_path', $token->getProviderKey());

        if ($session->has($key)) {
            return $session->get($key);
        }

        return null;
    }

    private function generateConfirmationEmail(User $user): Email
    {
        $username = $user->getDisplayName();
        $language = $user->getLanguage();

        $url = $this->generateUrl('fos_user_registration_confirm', ['token' => $user->getConfirmationToken()], UrlGeneratorInterface::ABSOLUTE_URL);

        return (new TemplatedEmail())
            ->to(new Address($user->getEmail()))
            ->subject(
                $this->getTranslator()->trans('registration.subject', ['%username%' => $username], 'email', $language)
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
