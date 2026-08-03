<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\Activity;
use App\Entity\Project;
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
    public const CREATE = 'create';
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
        'duplicate',
        'is_owner',
        self::CREATE
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

        if (!($user instanceof User) || $user->getId() === null) {
            return false;
        }

        $permission = '';

        switch ($attribute) {
            case 'is_owner':
                return (!$subject instanceof MultiUserTimesheet) && $user->getId() === $subject->getUser()?->getId();

            case self::CREATE:
                if (!$this->canCreate($user, $subject)) {
                    return false;
                }
                $permission .= $attribute;
                break;

            case self::START:
                if (!$this->canStart($user, $subject)) {
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
                if (!$this->canStart($user, $subject)) {
                    return false;
                }
                $permission = self::CREATE;
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

        if ($subject->getUser()?->getId() === $user->getId()) {
            return $this->permissionManager->hasRolePermission($user, $permission . '_own_timesheet');
        }

        if (!$this->permissionManager->checkTeamAccessTimesheet($subject, $user)) {
            return false;
        }

        return $this->permissionManager->hasRolePermission($user, $permission . '_other_timesheet');
    }

    private function canCreate(User $user, Timesheet $timesheet): bool
    {
        $activity = $timesheet->getActivity();
        if (null !== $activity && !$this->checkActivity($user, $activity)) {
            return false;
        }

        $project = $timesheet->getProject();
        if (null !== $project && !$this->checkProject($user, $project)) {
            return false;
        }

        return true;
    }

    private function canStart(User $user, Timesheet $timesheet): bool
    {
        $activity = $timesheet->getActivity();
        if (null === $activity || !$this->checkActivity($user, $activity)) {
            return false;
        }

        $project = $timesheet->getProject();
        if (null === $project || !$this->checkProject($user, $project)) {
            return false;
        }

        return true;
    }

    private function checkProject(User $user, Project $project): bool
    {
        if (!$project->isVisible()) {
            return false;
        }

        if (!$project->getCustomer()->isVisible()) {
            return false;
        }

        // starting and duplicating both create a NEW record under the referenced
        // project and activity, so the current user must still have team-based
        // access to them - historical ownership of the original timesheet is not
        // sufficient (otherwise old entries would survive an access revocation).
        if (!$this->permissionManager->checkTeamAccessProject($project, $user)) {
            return false;
        }

        return true;
    }

    private function checkActivity(User $user, Activity $activity): bool
    {
        if (!$activity->isVisible()) {
            return false;
        }

        if (!$this->permissionManager->checkTeamAccessActivity($activity, $user)) {
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
