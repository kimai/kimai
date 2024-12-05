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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class TwoFactorCondition implements TwoFactorConditionInterface
{
    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public function shouldPerformTwoFactorAuthentication(AuthenticationContextInterface $context): bool
    {
        // never require 2FA on API calls
        if (str_starts_with($context->getRequest()->getRequestUri(), '/api/')) {
            return false;
        }

        // if a user is remembered, it means he already passed the TOTP code
        // do not bother again with the code
        return !$this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }
}
