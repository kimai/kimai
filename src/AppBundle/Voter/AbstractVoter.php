<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Abstract voter to help with checking user roles.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
abstract class AbstractVoter extends Voter
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
     * @param string $role
     * @param TokenInterface $token
     * @return bool
     */
    public function hasRole($role, TokenInterface $token)
    {
        if ($this->decisionManager->decide($token, array($role))) {
            return true;
        }

        return false;
    }
}
