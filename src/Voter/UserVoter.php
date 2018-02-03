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
    const VIEW = 'view';
    const EDIT = 'edit';
    const CREATE = 'create';
    const DELETE = 'delete';
    const PASSWORD = 'password';
    const ROLES = 'roles';
    const PREFERENCES = 'preferences';

    const ALLOWED_ATTRIBUTES = [
        self::VIEW,
        self::EDIT,
        self::CREATE,
        self::ROLES,
        self::PASSWORD,
        self::DELETE,
        self::PREFERENCES
    ];

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, self::ALLOWED_ATTRIBUTES)) {
            return false;
        }

        if (!$subject instanceof User) {
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

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($subject, $user, $token);
            case self::EDIT:
            case self::PASSWORD:
            case self::PREFERENCES:
                return $this->canEdit($subject, $user, $token);
            case self::DELETE:
                return $this->canDelete($subject, $user, $token);
            case self::CREATE: // create actually passes in the current user as $subject, not the new one
            case self::ROLES:
                return $this->canAdminUsers($token);
        }

        return false;
    }

    /**
     * @param User $profile
     * @param User $user
     * @return bool
     */
    protected function canView(User $profile, User $user, TokenInterface $token)
    {
        if ($this->canEdit($profile, $user, $token)) {
            return true;
        }

        return $profile->getId() == $user->getId();
    }

    /**
     * @param User $profile
     * @param User $user
     * @return bool
     */
    protected function canEdit(User $profile, User $user, TokenInterface $token)
    {
        if ($this->canAdminUsers($token)) {
            return true;
        }

        return $profile->getId() == $user->getId();
    }

    /**
     * @param User $profile
     * @param User $user
     * @return bool
     */
    protected function canDelete(User $profile, User $user, TokenInterface $token)
    {
        return false;
    }

    /**
     * @param TokenInterface $token
     * @return bool
     */
    protected function canAdminUsers(TokenInterface $token)
    {
        return $this->isFullyAuthenticated($token) && $this->hasRole('ROLE_SUPER_ADMIN', $token);
    }
}
