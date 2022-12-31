<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use Pagerfanta\Exception\NotValidMaxPerPageException;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Catches Pagerfanta Exceptions and converts them to "normal" Http Exceptions with status code 404.
 * This was mainly done to convert the 500 to 404 HTTP response code.
 * This prevents also the need to register them in fos_rest.yaml for the API.
 */
final class PagerfantaExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onCoreException', 1]
        ];
    }

    public function onCoreException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof NotValidMaxPerPageException || $throwable instanceof OutOfRangeCurrentPageException) {
            $notFoundHttpException = new NotFoundHttpException($throwable->getMessage(), $throwable, $throwable->getCode());
            $event->setThrowable($notFoundHttpException);
        }
    }
}
