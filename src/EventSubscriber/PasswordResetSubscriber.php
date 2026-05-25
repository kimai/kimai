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

class PasswordResetSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AuthorizationCheckerInterface $security,
        private readonly TokenStorageInterface $storage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // higher priority is executed earlier - need to be higher than wizard
            KernelEvents::REQUEST => ['onKernelRequest', -20]
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // ignore sub-requests
        if (!$event->isMainRequest() || null === ($token = $this->storage->getToken())) {
            return;
        }

        $uri = $event->getRequest()->getRequestUri();

        // never trigger password reset on API calls
        // TODO 3.0 remove /register/
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

        if (!$user->requiresPasswordReset()) {
            return;
        }

        $response = new RedirectResponse($this->urlGenerator->generate('wizard', ['wizard' => 'password']));
        $event->setResponse($response);
    }
}
