<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use App\Security\RolePermissionManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Activity|Project|Customer|string>
 */
final class EntityMultiRoleVoter extends Voter
{
    /**
     * support rules based on the given activity/project/customer
     */
    private const ALLOWED_ATTRIBUTES = [
        'budget_money',
        'budget_time',
        'budget_any',
        'details',
        'listing',
    ];
    private const ALLOWED_SUBJECTS = [
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

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$this->supportsAttribute($attribute)) {
            return false;
        }

        if (\is_string($subject) && \in_array($subject, self::ALLOWED_SUBJECTS, true)) {
            return true;
        }

        if ($subject instanceof Activity || $subject instanceof Project || $subject instanceof Customer) {
            return true;
        }

        return false;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $suffix = null;

        if (\is_string($subject) && \in_array($subject, self::ALLOWED_SUBJECTS, true)) {
            $suffix = $subject;
        } elseif ($subject instanceof Activity) {
            $suffix = 'activity';
        } elseif ($subject instanceof Project) {
            $suffix = 'project';
        } elseif ($subject instanceof Customer) {
            $suffix = 'customer';
        }

        if ($suffix === null) {
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
            if ($this->permissionManager->hasRolePermission($user, $permission . '_' . $suffix)) {
                return true;
            }
        }

        return false;
    }
}
