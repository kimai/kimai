<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Form\Model\MultiUserTimesheet;
use App\Security\RolePermissionManager;
use App\Timesheet\LockdownService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * A voter to check permissions on Timesheets.
 *
 * @extends Voter<string, Timesheet>
 */
final class TimesheetVoter extends Voter
{
    public const VIEW = 'view';
    public const START = 'start';
    public const STOP = 'stop';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const EXPORT = 'export';
    public const VIEW_RATE = 'view_rate';
    public const EDIT_RATE = 'edit_rate';
    public const EDIT_EXPORT = 'edit_export';

    /**
     * support rules based on the given $subject (here: Timesheet)
     */
    private const ALLOWED_ATTRIBUTES = [
        self::VIEW,
        self::START,
        self::STOP,
        self::EDIT,
        self::DELETE,
        self::EXPORT,
        self::VIEW_RATE,
        self::EDIT_RATE,
        self::EDIT_EXPORT,
        'edit_billable',
        'duplicate'
    ];

    private ?bool $lockdownGrace = null;
    private ?bool $lockdownOverride = null;
    private ?bool $editExported = null;
    private ?\DateTime $now = null;

    public function __construct(
        private readonly RolePermissionManager $permissionManager,
        private readonly LockdownService $lockdownService
    )
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return \in_array($attribute, self::ALLOWED_ATTRIBUTES, true);
    }

    public function supportsType(string $subjectType): bool
    {
        return str_contains($subjectType, Timesheet::class) || str_contains($subjectType, MultiUserTimesheet::class);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Timesheet && $this->supportsAttribute($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!($user instanceof User)) {
            return false;
        }

        $permission = '';

        switch ($attribute) {
            case self::START:
                if (!$this->canStart($subject)) {
                    return false;
                }
                $permission .= $attribute;
                break;

            case self::EDIT:
                if (!$this->canEdit($user, $subject)) {
                    return false;
                }
                $permission .= $attribute;
                break;

            case self::DELETE:
                if (!$this->canDelete($user, $subject)) {
                    return false;
                }
                $permission .= $attribute;
                break;

            case 'duplicate':
                if (!$this->canStart($subject)) {
                    return false;
                }
                $permission = self::EDIT;
                break;

            case self::VIEW_RATE:
            case self::EDIT_RATE:
            case self::STOP:
            case self::VIEW:
            case self::EXPORT:
            case self::EDIT_EXPORT:
            case 'edit_billable':
                $permission .= $attribute;
                break;

            default:
                return false;
        }

        $permission .= '_';

        // extend me for "team" support later on
        if ($subject->getUser()?->getId() === $user->getId()) {
            $permission .= 'own';
        } else {
            $permission .= 'other';
        }

        $permission .= '_timesheet';

        return $this->permissionManager->hasRolePermission($user, $permission);
    }

    private function canStart(Timesheet $timesheet): bool
    {
        // possible improvements for the future:
        // we could check the amount of active entries (maybe slow)
        // if a teamlead starts an entry for another user, check that this user is part of his team (needs to be done for teams)

        if (null === $timesheet->getActivity()) {
            return false;
        }

        if (null === $timesheet->getProject()) {
            return false;
        }

        if (!$timesheet->getProject()->isVisible()) {
            return false;
        }

        if (!$timesheet->getProject()->getCustomer()->isVisible()) {
            return false;
        }

        if (!$timesheet->getActivity()->isVisible()) {
            return false;
        }

        return true;
    }

    private function canEdit(User $user, Timesheet $timesheet): bool
    {
        if (!$this->isAllowedExported($user, $timesheet)) {
            return false;
        }

        if (!$this->isAllowedInLockdown($user, $timesheet)) {
            return false;
        }

        return true;
    }

    private function canDelete(User $user, Timesheet $timesheet): bool
    {
        if (!$this->isAllowedExported($user, $timesheet)) {
            return false;
        }

        if (!$this->isAllowedInLockdown($user, $timesheet)) {
            return false;
        }

        return true;
    }

    private function isAllowedExported(User $user, Timesheet $timesheet): bool
    {
        if (!$timesheet->isExported()) {
            return true;
        }

        if ($this->editExported === null) {
            $this->editExported = $this->permissionManager->hasRolePermission($user, 'edit_exported_timesheet');
        }

        return $this->editExported;
    }

    private function isAllowedInLockdown(User $user, Timesheet $timesheet): bool
    {
        if (!$this->lockdownService->isLockdownActive()) {
            return true;
        }

        if ($this->lockdownOverride === null) {
            $this->lockdownOverride = $this->permissionManager->hasRolePermission($user, 'lockdown_override_timesheet');
        }

        if ($this->lockdownOverride) {
            return true;
        }

        if ($this->lockdownGrace === null) {
            $this->lockdownGrace = $this->permissionManager->hasRolePermission($user, 'lockdown_grace_timesheet');
        }

        if ($this->now === null) {
            $this->now = new \DateTime('now', new \DateTimeZone($user->getTimezone()));
        }

        return $this->lockdownService->isEditable($timesheet, $this->now, $this->lockdownGrace);
    }
}
