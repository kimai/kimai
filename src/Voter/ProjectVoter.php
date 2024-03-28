<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\Project;
use App\Entity\Team;
use App\Entity\User;
use App\Security\RolePermissionManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * A voter to check permissions on Projects.
 *
 * @extends Voter<string, Project>
 */
final class ProjectVoter extends Voter
{
    /**
     * support rules based on the given project
     */
    private const ALLOWED_ATTRIBUTES = [
        'view',
        'edit',
        'budget',
        'time',
        'delete',
        'permissions',
        'comments',
        'details',
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
        return str_contains($subjectType, Project::class);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Project && $this->supportsAttribute($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->permissionManager->hasRolePermission($user, $attribute . '_project')) {
            return true;
        }

        // those cannot be assigned to teams
        if (\in_array($attribute, ['create', 'delete'], true)) {
            return false;
        }

        $hasTeamleadPermission = $this->permissionManager->hasRolePermission($user, $attribute . '_teamlead_project');
        $hasTeamPermission = $this->permissionManager->hasRolePermission($user, $attribute . '_team_project');

        if (!$hasTeamleadPermission && !$hasTeamPermission) {
            return false;
        }

        /** @var Team $team */
        foreach ($subject->getTeams() as $team) {
            if ($hasTeamleadPermission && $user->isTeamleadOf($team)) {
                return true;
            }

            if ($hasTeamPermission && $user->isInTeam($team)) {
                return true;
            }
        }

        // new projects have no customer
        if (null === ($customer = $subject->getCustomer())) {
            return false;
        }

        /** @var Team $team */
        foreach ($customer->getTeams() as $team) {
            if ($hasTeamleadPermission && $user->isTeamleadOf($team)) {
                return true;
            }

            if ($hasTeamPermission && $user->isInTeam($team)) {
                return true;
            }
        }

        return false;
    }
}
