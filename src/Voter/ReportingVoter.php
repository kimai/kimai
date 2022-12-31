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

final class ReportingVoter extends Voter
{
    private const ALLOWED_ATTRIBUTES = [
        'report:customer',
        'report:other',
        'report:project',
        'report:user',
    ];

    public function __construct(private RolePermissionManager $permissionManager)
    {
    }

    /**
     * @param string $attribute
     * @param null $subject
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject === null && \in_array($attribute, self::ALLOWED_ATTRIBUTES);
    }

    /**
     * @param string $attribute
     * @param null $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $permissions = ['view_reporting'];

        switch ($attribute) {
            case 'report:customer':
                $permissions[] = 'customer_reporting';
                break;

            case 'report:other':
                $permissions[] = 'view_other_reporting';
                $permissions[] = 'view_other_timesheet';
                break;

            case 'report:project':
                $permissions[] = 'project_reporting';
                break;

            case 'report:user':
                // own reports are always allowed if reporting can be accessed
                break;
        }

        foreach ($permissions as $permission) {
            if (!$this->permissionManager->hasRolePermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }
}
