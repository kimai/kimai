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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * A voter to check permissions on Projects.
 */
class ProjectVoter extends AbstractVoter
{
    /**
     * support rules based on the given project
     */
    public const ALLOWED_ATTRIBUTES = [
        'view',
        'edit',
        'budget',
        'delete',
        'permissions',
        'comments',
        'comments_create',
        'details',
    ];

    /**
     * @param string $attribute
     * @param Project $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!($subject instanceof Project)) {
            return false;
        }

        if (!\in_array($attribute, self::ALLOWED_ATTRIBUTES)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param Project $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->hasRolePermission($user, $attribute . '_project')) {
            return true;
        }

        // those cannot be assigned to teams
        if (\in_array($attribute, ['create', 'delete'])) {
            return false;
        }

        $hasTeamleadPermission = $this->hasRolePermission($user, $attribute . '_teamlead_project');
        $hasTeamPermission = $this->hasRolePermission($user, $attribute . '_team_project');

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
