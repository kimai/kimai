<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Utils\ProfileManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class ProfileSubscriber implements EventSubscriberInterface
{
    public function __construct(private ProfileManager $profileManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // We do not use the InteractiveLoginEvent because it is not triggered e.g. for SAML
            LoginSuccessEvent::class => 'onFormLogin',
        ];
    }

    public function onFormLogin(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();

        // make sure that we do NOT access the session, if the request is stateless
        if ($request->attributes->getBoolean('_stateless')) {
            return;
        }

        $profile = $this->profileManager->getProfileFromCookie($request);
        if ($this->profileManager->isValidProfile($profile)) {
            $this->profileManager->setProfile($request->getSession(), $profile);
        }
    }
}
