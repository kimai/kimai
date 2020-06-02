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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * A voter to check permissions on Timesheets.
 */
class TimesheetVoter extends AbstractVoter
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
    public const ALLOWED_ATTRIBUTES = [
        self::VIEW,
        self::START,
        self::STOP,
        self::EDIT,
        self::DELETE,
        self::EXPORT,
        self::VIEW_RATE,
        self::EDIT_RATE,
        self::EDIT_EXPORT,
        'duplicate'
    ];

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!($subject instanceof Timesheet)) {
            return false;
        }

        if (!\in_array($attribute, self::ALLOWED_ATTRIBUTES)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param Timesheet $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
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
                $permission = self::EDIT;
                break;

            case self::VIEW_RATE:
            case self::EDIT_RATE:
            case self::STOP:
            case self::VIEW:
            case self::EXPORT:
            case self::EDIT_EXPORT:
                $permission .= $attribute;
                break;

            default:
                return false;
        }

        $permission .= '_';

        // extend me for "team" support later on
        if ($subject->getUser()->getId() == $user->getId()) {
            $permission .= 'own';
        } else {
            $permission .= 'other';
        }

        $permission .= '_timesheet';

        return $this->hasRolePermission($user, $permission);
    }

    protected function canStart(Timesheet $timesheet): bool
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

        if (!$timesheet->getActivity()->isVisible() || !$timesheet->getProject()->isVisible()) {
            return false;
        }

        if (!$timesheet->getProject()->getCustomer()->isVisible()) {
            return false;
        }

        return true;
    }

    protected function canEdit(User $user, Timesheet $timesheet): bool
    {
        if ($timesheet->isExported() && !$this->hasRolePermission($user, 'edit_exported_timesheet')) {
            return false;
        }

        return true;
    }

    protected function canDelete(User $user, Timesheet $timesheet): bool
    {
        if ($timesheet->isExported() && !$this->hasRolePermission($user, 'edit_exported_timesheet')) {
            return false;
        }

        return true;
    }
}
