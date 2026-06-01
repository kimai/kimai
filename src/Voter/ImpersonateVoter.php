<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\User;
use App\Security\RolePermissionManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * A voter to check if the current user can impersonate other users.
 *
 * @extends Voter<string, null>
 */
final class ImpersonateVoter extends Voter
{
    public function __construct(private readonly RolePermissionManager $permissionManager)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === 'impersonate_user';
    }

    public function supportsType(string $subjectType): bool
    {
        return str_contains($subjectType, User::class);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof User && $this->supportsAttribute($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous or if the subject is not a user, do not grant access
        if (!$user instanceof User || !$subject instanceof User) {
            return false;
        }

        // never ever might any other user use impersonation
        if (!$user->isSuperAdmin()) {
            return false;
        }

        return $this->permissionManager->hasRolePermission($user, 'impersonate_user');
    }
}
