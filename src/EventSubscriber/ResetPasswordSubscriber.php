<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Makes sure that only internally registered users can reset their password.
 */
class ResetPasswordSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FOSUserEvents::RESETTING_SEND_EMAIL_INITIALIZE => ['onInitializeResetPassword', 200]
        ];
    }

    public function onInitializeResetPassword(GetResponseNullableUserEvent $event)
    {
        $user = $event->getUser();
        if (!($user instanceof User)) {
            return;
        }

        // that is not nice :-D
        if (!$user->isInternalUser()) {
            throw new AccessDeniedHttpException(
                sprintf('The user "%s" tried to reset the password, but it is registered as "%s" auth-type.', $user->getUsername(), $user->getAuth())
            );
        }
    }
}
