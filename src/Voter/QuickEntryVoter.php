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
use App\Timesheet\TrackingModeService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed>
 */
final class QuickEntryVoter extends Voter
{
    public function __construct(
        private readonly RolePermissionManager $permissionManager,
        private readonly TrackingModeService $trackingModeService
    )
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return 'quick-entry' === $attribute;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!($user instanceof User)) {
            return false;
        }

        if (!$this->permissionManager->hasRolePermission($user, 'weekly_own_timesheet')) {
            return false;
        }

        if (!$this->permissionManager->hasRolePermission($user, 'edit_own_timesheet')) {
            return false;
        }

        $mode = $this->trackingModeService->getActiveMode();

        if ($mode->canEditDuration() || $mode->canEditEnd()) {
            return true;
        }

        return false;
    }
}
