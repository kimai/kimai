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
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Answers the question "may this user see that kind of data on any customer, project or
 * activity?" and is used to decide whether a column, a menu or a report is rendered at all.
 *
 * It deliberately works on the entity type and not on a single entity: a permission like
 * "budget_teamlead_project" says nothing about one specific project.
 * Use the CustomerVoter, ProjectVoter and ActivityVoter to check a concrete object.
 *
 * @extends Voter<string, string>
 */
final class EntityMultiRoleVoter extends Voter
{
    private const array ALLOWED_ATTRIBUTES = [
        'budget_money',
        'budget_time',
        'budget_any',
        'details',
        'listing',
    ];
    private const array ALLOWED_SUBJECTS = [
        'customer',
        'project',
        'activity',
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
        return $subjectType === 'string';
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$this->supportsAttribute($attribute)) {
            return false;
        }

        return \is_string($subject) && \in_array($subject, self::ALLOWED_SUBJECTS, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $permissions = [];

        if ($attribute === 'details') {
            $permissions[] = 'details';
        }

        if ($attribute === 'budget_money' || $attribute === 'budget_any') {
            $permissions[] = 'budget';
            $permissions[] = 'budget_teamlead';
            $permissions[] = 'budget_team';
        }

        if ($attribute === 'budget_time' || $attribute === 'budget_any') {
            $permissions[] = 'time';
            $permissions[] = 'time_teamlead';
            $permissions[] = 'time_team';
        }

        if ($attribute === 'listing') {
            $permissions[] = 'view';
            $permissions[] = 'view_team';
            $permissions[] = 'view_teamlead';
        }

        foreach ($permissions as $permission) {
            if ($this->permissionManager->hasRolePermission($user, $permission . '_' . $subject)) {
                return true;
            }
        }

        return false;
    }
}
