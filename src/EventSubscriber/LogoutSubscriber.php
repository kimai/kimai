<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Security\SessionHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SessionHandler $sessionHandler,
        private readonly LoggerInterface $logger
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        try {
            $this->sessionHandler->garbageCollection();
        } catch (\Exception $exception) {
            $this->logger->error('Failed removing expired session: ' . $exception->getMessage());
        }
    }
}
