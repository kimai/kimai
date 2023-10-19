<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class WizardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private AuthorizationCheckerInterface $security,
        private TokenStorageInterface $storage
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest']
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // ignore sub-requests
        if (!$event->isMainRequest()) {
            return;
        }

        // ignore events like the toolbar where we do not have a token
        if (null === ($token = $this->storage->getToken())) {
            return;
        }

        $uri = $event->getRequest()->getRequestUri();

        // never require 2FA on API calls
        if (str_starts_with($uri, '/api/') || stripos($uri, '/register/') !== false || stripos($uri, '/wizard/') !== false) {
            return;
        }

        $user = $token->getUser();

        if (!($user instanceof User)) {
            return;
        }

        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return;
        }

        foreach (User::WIZARDS as $wizard) {
            if (!$user->hasSeenWizard($wizard)) {
                $response = new RedirectResponse($this->urlGenerator->generate('wizard', ['wizard' => $wizard]));
                $event->setResponse($response);

                return;
            }
        }

        if ($user->requiresPasswordReset()) {
            $response = new RedirectResponse($this->urlGenerator->generate('wizard', ['wizard' => 'password']));
            $event->setResponse($response);
        }
    }
}
