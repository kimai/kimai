<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Replaces the Symfony default AppVariable, which exposes security relevant information.
 */
final class AppVariable
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage
    )
    {
    }

    public function getLocale(): string
    {
        return $this->requestStack->getMainRequest()?->getLocale() ?? 'en';
    }

    public function getUser(): ?UserInterface
    {
        return $this->tokenStorage->getToken()?->getUser();
    }

    public function getCurrent_route(): ?string
    {
        $route = $this->requestStack->getCurrentRequest()?->attributes->get('_route');
        if (!\is_string($route)) {
            return null;
        }

        return $route;
    }

    /**
     * @return array<string, string[]>
     */
    public function getFlashes(): array
    {
        $session = $this->getSession();

        if (!$session instanceof FlashBagAwareSessionInterface) {
            return [];
        }

        return $session->getFlashBag()->all();
    }

    private function getSession(): ?SessionInterface
    {
        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
        }

        return null;
    }

    /**
     * The request should not be exposed under any circumstance to the frontend.
     * This here is added as fallback for old customer templates still using this object.
     *
     * @return array{locale: string}
     */
    public function getRequest(): array
    {
        return [
            'locale' => $this->getLocale(),
            'pathinfo' => $this->requestStack->getCurrentRequest()?->getPathInfo(),
        ];
    }
}
