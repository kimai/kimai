<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\Customer;
use App\Entity\Team;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * A voter to check authorization on Customers.
 */
class CustomerVoter extends AbstractVoter
{
    /**
     * supported attributes/rules based on the given customer
     */
    public const ALLOWED_ATTRIBUTES = [
        'view',
        'create',
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
     * @param Customer $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!($subject instanceof Customer)) {
            return false;
        }

        if (!\in_array($attribute, self::ALLOWED_ATTRIBUTES)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param Customer $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->hasRolePermission($user, $attribute . '_customer')) {
            return true;
        }

        // those cannot be assigned to teams
        if (\in_array($attribute, ['create', 'delete'])) {
            return false;
        }

        $hasTeamleadPermission = $this->hasRolePermission($user, $attribute . '_teamlead_customer');
        $hasTeamPermission = $this->hasRolePermission($user, $attribute . '_team_customer');

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

        return false;
    }
}
