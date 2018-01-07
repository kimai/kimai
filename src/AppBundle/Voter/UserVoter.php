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

use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * A voter to check permissions on user profiles.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class UserVoter extends AbstractVoter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const CREATE = 'create';
    const DELETE = 'delete';
    const PASSWORD = 'password';
    const ROLES = 'roles';
    const VIEW_ALL = 'view_all';

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array(
            $attribute,
            [self::VIEW, self::VIEW_ALL, self::EDIT, self::CREATE, self::ROLES, self::PASSWORD, self::DELETE]
        )) {
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
                return $this->canEdit($subject, $user, $token);
            case self::VIEW_ALL:
            case self::CREATE:
                // create actually passes in the current user as $subject, not the new one
            case self::DELETE:
                // if we allow to delete user for ADMIN: make sure the user to be deleted is not in a higher level
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
     * @param TokenInterface $token
     * @return bool
     */
    protected function canAdminUsers(TokenInterface $token)
    {
        return $this->hasRole('ROLE_SUPER_ADMIN', $token);
    }
}
