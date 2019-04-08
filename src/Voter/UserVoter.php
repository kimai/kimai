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
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const PASSWORD = 'password';
    public const ROLES = 'roles';
    public const PREFERENCES = 'preferences';
    public const API_TOKEN = 'api-token';
    public const HOURLY_RATE = 'hourly-rate';

    public const ALLOWED_ATTRIBUTES = [
        self::VIEW,
        self::EDIT,
        self::ROLES,
        self::PASSWORD,
        self::DELETE,
        self::PREFERENCES,
        self::API_TOKEN,
        self::HOURLY_RATE,
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

        if (!in_array($attribute, self::ALLOWED_ATTRIBUTES)) {
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

        $permission = '';

        switch ($attribute) {
            // special case for the UserController
            case self::DELETE:
                if (!$this->canDelete($subject, $user)) {
                    return false;
                }

                return $this->hasRolePermission($user, 'delete_user');

            // used in templates and ProfileController
            case self::VIEW:
            case self::EDIT:
            case self::API_TOKEN:
            case self::PASSWORD:
            case self::ROLES:
            case self::PREFERENCES:
            case self::HOURLY_RATE:
                $permission .= $attribute;
                break;

            default:
                return false;
        }

        $permission .= '_';

        // extend me for "team" support later on
        if ($subject->getId() == $user->getId()) {
            $permission .= 'own';
        } else {
            $permission .= 'other';
        }

        $permission .= '_profile';

        return $this->hasRolePermission($user, $permission);
    }

    /**
     * @param User $profile
     * @param User $user
     * @return bool
     */
    protected function canDelete(User $profile, User $user)
    {
        return $profile->getId() !== $user->getId();
    }
}
