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
 * A voter to check the free-configurable permission from "kimai.permissions".
 *
 * @extends Voter<string, null>
 */
final class RolePermissionVoter extends Voter
{
    public function __construct(private readonly RolePermissionManager $permissionManager)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return $this->permissionManager->isRegisteredPermission($attribute);
    }

    public function supportsType(string $subjectType): bool
    {
        // we only work on single strings that have no subject
        return $subjectType === 'null';
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject === null && $this->supportsAttribute($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!($user instanceof User)) {
            return false;
        }

        return $this->permissionManager->hasRolePermission($user, $attribute);
    }
}
