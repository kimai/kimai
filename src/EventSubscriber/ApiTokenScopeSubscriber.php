<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\API\Permission\ApiTokenScopeMap;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Enforces the configured scopes of an API access token.
 *
 * Runs only for requests that were authenticated via an API access token (Bearer).
 * Frontend/session requests and the deprecated API-password flow do not fill that
 * and are therefore never restricted here.
 *
 * Legacy tokens (scopes = null) keep full access. Endpoints without an
 * #[ApiToken] declaration are allowed by default (see spec §6).
 */
final class ApiTokenScopeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ApiTokenScopeMap $scopeMap,
        private readonly Security $security
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', 0],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->security->getToken();
        if ($token === null) {
            return;
        }

        $attributes = $token->getAttributes();
        // not an API-token request (frontend/session or legacy API password)
        if (!\array_key_exists('api-scopes', $attributes)) {
            return;
        }

        $allowedScopes = $attributes['api-scopes'];
        // legacy tokens (scopes = null) run with the full permissions of their user;
        // an explicit (even empty) scope set is enforced below
        if ($allowedScopes === null) {
            return;
        }

        $routeName = $event->getRequest()->attributes->get('_route');
        $requiredScope = $this->scopeMap->getRequiredScope(\is_string($routeName) ? $routeName : null);

        // undeclared or explicitly ignored endpoint -> allowed
        if ($requiredScope === null) {
            return;
        }

        if (!\is_array($allowedScopes) || !\in_array($requiredScope, $allowedScopes, true)) {
            throw new AccessDeniedException(\sprintf('The used API token is missing the required scope "%s".', $requiredScope));
        }
    }
}
