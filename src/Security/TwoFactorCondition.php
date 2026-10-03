<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Condition\TwoFactorConditionInterface;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class TwoFactorCondition implements TwoFactorConditionInterface
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
    {
        // never require 2FA on API calls
        if (str_starts_with($context->getRequest()->getRequestUri(), '/api/')) {
            return false;
        }

        // this is called BEFORE the new token is stored, so the token storage still holds
        // the token of the previous request (e.g. a remember-me or a fully authenticated session)
        $current = $this->tokenStorage->getToken();

        // only a remember-me token proves that the TOTP code was already validated:
        // the remember-me cookie is issued after the 2FA was completed (see SuppressRememberMeListener).
        // any other token (e.g. from a fully authenticated session) must not skip the 2FA.
        if (!$current instanceof RememberMeToken) {
            return true;
        }

        // the remembered user must be the one who is logging in: otherwise an attacker with
        // a remember-me cookie for his own account could log in as another user (whose password
        // he knows) and skip the TOTP challenge of that account entirely
        return $current->getUserIdentifier() !== $context->getUser()->getUserIdentifier();
    }
}
