<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\Activity;
use App\Entity\Team;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * A voter to check permissions on Activities.
 */
class ActivityVoter extends AbstractVoter
{
    /**
     * support rules based on the given activity
     */
    public const ALLOWED_ATTRIBUTES = [
        'view',
        'edit',
        'budget',
        'comments',
        'comments_create',
        'delete',
        'permissions',
    ];

    /**
     * @param string $attribute
     * @param Activity $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!($subject instanceof Activity)) {
            return false;
        }

        if (!\in_array($attribute, self::ALLOWED_ATTRIBUTES)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param Activity $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->hasRolePermission($user, $attribute . '_activity')) {
            return true;
        }

        // those cannot be assigned to teams
        if (\in_array($attribute, ['create', 'delete'])) {
            return false;
        }

        $hasTeamleadPermission = $this->hasRolePermission($user, $attribute . '_teamlead_activity');
        $hasTeamPermission = $this->hasRolePermission($user, $attribute . '_team_activity');

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

        // new and global activities have no project
        if (null === ($project = $subject->getProject())) {
            return false;
        }

        /** @var Team $team */
        foreach ($project->getTeams() as $team) {
            if ($hasTeamleadPermission && $user->isTeamleadOf($team)) {
                return true;
            }

            if ($hasTeamPermission && $user->isInTeam($team)) {
                return true;
            }
        }

        if (null === ($customer = $project->getCustomer())) {
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
