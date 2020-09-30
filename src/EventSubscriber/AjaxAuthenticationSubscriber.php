<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationExpiredException;

class AjaxAuthenticationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onCoreException', 1]
        ];
    }

    public function onCoreException(ExceptionEvent $event)
    {
        $request = $event->getRequest();

        if ($request->isXmlHttpRequest()) {
            $exception = $event->getThrowable();
            if ($exception instanceof AuthenticationExpiredException) {
                $event->setResponse(new Response('Session expired', 403, ['Login-Required' => true]));
            } elseif ($exception instanceof AuthenticationException) {
                $event->setResponse(new Response('Authentication problem', 403, ['Login-Required' => true]));
            } elseif ($exception instanceof AccessDeniedException) {
                $event->setResponse(new Response('Access denied', 403, ['Login-Required' => true]));
            }
        }
    }
}
