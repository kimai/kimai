<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Mail;

use App\Configuration\MailConfiguration;
use App\Constants;
use App\Entity\User;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class KimaiMailer implements MailerInterface
{
    public function __construct(private readonly MailConfiguration $configuration, private readonly MailerInterface $mailer)
    {
    }

    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        if (!$message instanceof Email) {
            $email = new Email();
            $email->text($message->toString());
            $message = $email;
        }

        if (\count($message->getFrom()) === 0) {
            $fallback = $this->configuration->getFromAddress();
            if ($fallback === null) {
                throw new \RuntimeException('Missing email "from" address');
            }
            $message->from(new Address($fallback, Constants::SOFTWARE));
        }

        $this->mailer->send($message);
    }

    public function sendToUser(User $user, Email $message, Envelope $envelope = null): void
    {
        if (!$user->isEnabled() || $user->getEmail() === null) {
            return;
        }

        $message->to($user->getEmail());

        $this->send($message, $envelope);
    }
}
