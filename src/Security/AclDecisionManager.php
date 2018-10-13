<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AclDecisionManager
{
    /**
     * @var AccessDecisionManagerInterface
     */
    protected $decisionManager;

    /**
     * AbstractVoter constructor.
     * @param AccessDecisionManagerInterface $decisionManager
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    /**
     * @param TokenInterface $token
     * @return bool
     */
    public function isFullyAuthenticated(TokenInterface $token)
    {
        if ($this->decisionManager->decide($token, ['IS_AUTHENTICATED_FULLY'])) {
            return true;
        }

        return false;
    }

    /**
     * @param TokenInterface $token
     * @param string|array $roles
     * @return bool
     */
    public function hasRole(TokenInterface $token, $roles)
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        if ($this->decisionManager->decide($token, $roles)) {
            return true;
        }

        return false;
    }
}
