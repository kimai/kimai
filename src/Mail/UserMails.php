<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Mail;

use App\Entity\User;
use FOS\UserBundle\Mailer\MailerInterface as FOSMailerInterface;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserMails implements FOSMailerInterface
{
    /**
     * @var KimaiMailer
     */
    private $mailer;
    /**
     * @var UrlGeneratorInterface
     */
    private $router;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(KimaiMailer $mailer, UrlGeneratorInterface $router, TranslatorInterface $translator)
    {
        $this->mailer = $mailer;
        $this->router = $router;
        $this->translator = $translator;
    }

    public function sendConfirmationEmailMessage(UserInterface $user)
    {
        $username = $user->getUsername();
        $language = 'en';

        if ($user instanceof User) {
            $username = $user->getDisplayName();
            $language = $user->getLanguage();
        }

        $url = $this->router->generate('fos_user_registration_confirm', ['token' => $user->getConfirmationToken()], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->to(new Address($user->getEmail()))
            ->subject(
                $this->translator->trans('registration.subject', ['%username%' => $username], 'email', $language)
            )
            ->htmlTemplate('emails/confirmation.html.twig')
            ->context([
                'user' => $user,
                'username' => $username,
                'confirmationUrl' => $url,
            ])
        ;

        $this->mailer->send($email);
    }

    public function sendResettingEmailMessage(UserInterface $user)
    {
        $username = $user->getUsername();
        $language = 'en';

        if ($user instanceof User) {
            $username = $user->getDisplayName();
            $language = $user->getLanguage();
        }

        $url = $this->router->generate('fos_user_resetting_reset', ['token' => $user->getConfirmationToken()], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->to(new Address($user->getEmail()))
            ->subject(
                $this->translator->trans('reset.subject', ['%username%' => $username], 'email', $language)
            )
            ->htmlTemplate('emails/password-reset.html.twig')
            ->context([
                'user' => $user,
                'username' => $username,
                'confirmationUrl' => $url,
            ])
        ;

        $this->mailer->send($email);
    }
}
