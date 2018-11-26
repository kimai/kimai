<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\Activity;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * A voter to check the free-configurable permission from "kimai.permissions".
 */
class RolePermissionVoter extends AbstractVoter
{
    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        // we only work on single strings that have no subject
        if (null !== $subject) {
            return false;
        }

        // and which is not neither a user role like USER_ADMIN
        // nor an implicit role like IS_REMEMBERED / IS_FULLY_AUTHENTICATED
        if (strpos($attribute, 'ROLE_') === false && strpos($attribute, 'IS_') === false) {
            return $this->isRegisteredPermission($attribute);
        }

        return false;
    }

    /**
     * @param string $attribute
     * @param Activity $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!($user instanceof User)) {
            return false;
        }

        foreach ($user->getRoles() as $role) {
            if ($this->hasPermission($role, $attribute)) {
                return true;
            }
        }

        return false;
    }
}
