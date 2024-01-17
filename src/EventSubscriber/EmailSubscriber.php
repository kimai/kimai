<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Event\EmailEvent;
use App\Mail\KimaiMailer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to handle emails.
 */
final class EmailSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly KimaiMailer $mailer)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvent::class => ['onMailEvent', 100],
        ];
    }

    public function onMailEvent(EmailEvent $event): void
    {
        $this->mailer->send($event->getEmail());
    }
}
