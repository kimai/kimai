<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * A voter to check permissions on user profiles.
 */
class UserVoter extends AbstractVoter
{
    public const ALLOWED_ATTRIBUTES = [
        'view',
        'edit',
        'roles',
        'teams',
        'password',
        'delete',
        'preferences',
        'api-token',
        'hourly-rate',
    ];

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!($subject instanceof User)) {
            return false;
        }

        if (!\in_array($attribute, self::ALLOWED_ATTRIBUTES)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param User $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!($user instanceof User)) {
            return false;
        }

        if ($attribute === 'delete') {
            if ($subject->getId() === $user->getId()) {
                return false;
            }

            return $this->hasRolePermission($user, 'delete_user');
        } elseif ($attribute === 'password') {
            if (!$subject->isInternalUser()) {
                return false;
            }
        }

        $permission = $attribute;

        // extend me for "team" support later on
        if ($subject->getId() === $user->getId()) {
            $permission .= '_own';
        } else {
            $permission .= '_other';
        }

        $permission .= '_profile';

        return $this->hasRolePermission($user, $permission);
    }
}
