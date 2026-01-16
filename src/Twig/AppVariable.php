<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

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
    public function __construct(private readonly RequestStack $requestStack, private readonly TokenStorageInterface $tokenStorage)
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
        return $this->requestStack->getCurrentRequest()->attributes->get('_route');
    }

    public function getFlashes(): array
    {
        $session = $this->getSession2();

        if (!$session instanceof FlashBagAwareSessionInterface) {
            return [];
        }

        return $session->getFlashBag()->all();
    }

    private function getSession2(): ?SessionInterface
    {
        try {
            if (null !== $session = $this->requestStack->getSession()) {
                return $session;
            }
        } catch (\RuntimeException) {
        }

        return null;
    }

    /**
     * The request should not be exposed under any circumstance to the frontend.
     * This here is added as fallback for old customer templates still using this object.
     */
    public function getRequest(): array
    {
        return [
            'locale' => $this->getLocale(),
            'pathinfo' => $this->requestStack->getCurrentRequest()?->getPathInfo(),
        ];
    }
}
