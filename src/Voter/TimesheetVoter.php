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
        self::START,
        self::STOP,
        self::EDIT,
        self::DELETE,
        self::EXPORT,
        self::VIEW_RATE,
        self::EDIT_RATE,
        self::EDIT_EXPORT,
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

        if (!in_array($attribute, self::ALLOWED_ATTRIBUTES)) {
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
                if (!$this->canStart($subject, $user, $token)) {
                    return false;
                }
                $permission .= $attribute;
                break;

            case self::VIEW_RATE:
            case self::EDIT_RATE:
            case self::STOP:
            case self::EDIT:
            case self::DELETE:
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

    /**
     * @param Timesheet $timesheet
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    protected function canStart(Timesheet $timesheet, User $user, TokenInterface $token)
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

        if (!$timesheet->getActivity()->getVisible() || !$timesheet->getProject()->getVisible()) {
            return false;
        }

        if (!$timesheet->getProject()->getCustomer()->getVisible()) {
            return false;
        }

        return true;
    }
}
