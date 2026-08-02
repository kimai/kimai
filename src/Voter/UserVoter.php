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
 * A voter to check permissions on user profiles.
 *
 * @extends Voter<string, User>
 */
final class UserVoter extends Voter
{
    private const ALLOWED_ATTRIBUTES = [
        'access_user',
        'view',
        'edit',
        'roles',
        'teams',
        'password',
        '2fa',
        'delete',
        'preferences',
        'api-token',
        'hourly-rate',
        'view_team_member',
        'contract',
        'hours',
        'supervisor',
    ];

    public function __construct(private readonly RolePermissionManager $permissionManager)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return \in_array($attribute, self::ALLOWED_ATTRIBUTES, true);
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

        if (!($user instanceof User) || !($subject instanceof User)) {
            return false;
        }

        if ($attribute === 'contract') {
            return $this->permissionManager->hasRolePermission($user, 'contract_other_profile');
        }

        if ($attribute === 'access_user') {
            return $this->permissionManager->checkUserAccess($subject, $user);
        }

        if ($attribute === 'view_team_member') {
            if ($subject->getId() !== $user->getId()) {
                return false;
            }

            return $this->permissionManager->hasRolePermission($user, 'view_team_member');
        }

        if ($attribute === 'delete') {
            if ($subject->getId() === $user->getId()) {
                return false;
            }

            return $this->permissionManager->hasRolePermission($user, 'delete_user');
        }

        if ($attribute === 'password') {
            if (!$subject->isInternalUser()) {
                return false;
            }
        }

        if ($attribute === '2fa') {
            // can only be activated by the logged-in user for himself or by a super-admin
            return $subject->getId() === $user->getId() || $user->isSuperAdmin();
        }

        if ($attribute === 'supervisor' && $subject->getId() === $user->getId()) {
            return $user->isSuperAdmin();
        }

        // a user must not edit the roles of someone who holds a role they are not allowed to assign
        // themselves - otherwise a lower-privileged user could strip a higher role from the subject
        if ($attribute === 'roles' && !$this->canManageAllRolesOf($subject, $user)) {
            return false;
        }

        $permission = $attribute;

        if ($subject->getId() === $user->getId()) {
            return $this->permissionManager->hasRolePermission($user, $permission . '_own_profile');
        }

        if (!$this->permissionManager->hasRolePermission($user, $permission . '_other_profile')) {
            return false;
        }

        return $this->permissionManager->checkUserAccess($subject, $user, false);
    }

    /**
     * Whether $user is allowed to assign every role the $subject currently holds.
     *
     * The rules mirror the assignable roles offered by {@see \App\Form\Type\UserRoleType}:
     * a super-admin role may only be managed by a super admin, an admin role only by an
     * admin (or above) and a teamlead role only by a teamlead (or above).
     */
    private function canManageAllRolesOf(User $subject, User $user): bool
    {
        if ($subject->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        if ($subject->isAdmin() && !$user->isAdmin(false)) {
            return false;
        }

        if ($subject->hasTeamleadRole() && !$user->hasTeamleadRole(false)) {
            return false;
        }

        return true;
    }
}
