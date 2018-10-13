<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Security\AclDecisionManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Abstract voter to help with checking user roles.
 */
abstract class AbstractVoter extends Voter
{
    /**
     * @var AclDecisionManager
     */
    protected $decisionManager;

    /**
     * AbstractVoter constructor.
     * @param AclDecisionManager $decisionManager
     */
    public function __construct(AclDecisionManager $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    /**
     * @param TokenInterface $token
     * @return bool
     */
    protected function isFullyAuthenticated(TokenInterface $token)
    {
        return $this->decisionManager->isFullyAuthenticated($token);
    }

    /**
     * @param string $role
     * @param TokenInterface $token
     * @return bool
     */
    protected function hasRole($role, TokenInterface $token)
    {
        return $this->decisionManager->hasRole($token, [$role]);
    }
}
