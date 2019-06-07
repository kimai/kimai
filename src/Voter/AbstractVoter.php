<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\User;
use App\Security\AclDecisionManager;
use App\Security\RolePermissionManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Abstract voter to help with checking user permissions.
 */
abstract class AbstractVoter extends Voter
{
    /**
     * @var AclDecisionManager
     */
    protected $decisionManager;
    /**
     * @var RolePermissionManager
     */
    protected $roleManager;

    /**
     * @param AclDecisionManager $decisionManager
     * @param RolePermissionManager $roleManager
     */
    public function __construct(AclDecisionManager $decisionManager, RolePermissionManager $roleManager)
    {
        $this->decisionManager = $decisionManager;
        $this->roleManager = $roleManager;
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
     * @param string $permission
     * @return bool
     */
    protected function hasPermission($role, $permission)
    {
        return $this->roleManager->hasPermission($role, $permission);
    }

    /**
     * @param User $user
     * @param string $permission
     * @return bool
     */
    protected function hasRolePermission(User $user, $permission)
    {
        foreach ($user->getRoles() as $role) {
            if ($this->hasPermission($role, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $permission
     * @return bool
     */
    public function isRegisteredPermission($permission)
    {
        return $this->roleManager->isRegisteredPermission($permission);
    }
}
