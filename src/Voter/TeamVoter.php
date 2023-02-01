<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\Team;
use App\Entity\User;
use App\Security\RolePermissionManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Team>
 */
final class TeamVoter extends Voter
{
    /**
     * support rules based on the given $subject (here: Team)
     */
    private const ALLOWED_ATTRIBUTES = [
        'view',
        'edit',
        'delete',
    ];

    public function __construct(private RolePermissionManager $permissionManager)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!($subject instanceof Team)) {
            return false;
        }

        if (!\in_array($attribute, self::ALLOWED_ATTRIBUTES)) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case 'edit':
            case 'delete':
                // changing existing teams should be limited to admins and teamleads
                if (!$user->isAdmin() && !$user->isSuperAdmin() && !$user->isTeamleadOf($subject)) {
                    return false;
                }
        }

        return $this->permissionManager->hasRolePermission($user, $attribute . '_team');
    }
}
