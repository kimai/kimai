<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Mail;

use App\Configuration\MailConfiguration;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class KimaiMailer implements MailerInterface
{
    /**
     * @var MailerInterface
     */
    private $mailer;
    /**
     * @var MailConfiguration
     */
    private $configuration;

    public function __construct(MailConfiguration $configuration, MailerInterface $mailer)
    {
        $this->configuration = $configuration;
        $this->mailer = $mailer;
    }

    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        if ($message instanceof Email) {
            $message->from($this->configuration->getFromAddress());
        }

        $this->mailer->send($message);
    }
}
