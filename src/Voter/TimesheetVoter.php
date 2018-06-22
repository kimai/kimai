<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use App\Entity\Activity;
use App\Entity\Timesheet;

/**
 * A voter to check permissions on Timesheets.
 */
class TimesheetVoter extends AbstractVoter
{
    public const START = 'start';
    public const STOP = 'stop';
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    public const ALLOWED_ATTRIBUTES = [
        self::START,
        self::STOP,
        self::VIEW,
        self::EDIT,
        self::DELETE
    ];

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, self::ALLOWED_ATTRIBUTES)) {
            return false;
        }

        if ($subject instanceof Activity && self::START == $attribute) {
            return true;
        }

        if (!$subject instanceof Timesheet) {
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

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::STOP:
                return $this->canStop($subject, $user, $token);
            case self::START:
                return $this->canStart($subject, $user, $token);
            case self::VIEW:
                return $this->canView($subject, $user, $token);
            case self::EDIT:
                return $this->canEdit($subject, $user, $token);
            case self::DELETE:
                return $this->canDelete($subject, $user, $token);
        }

        return false;
    }

    /**
     * @param Timesheet $timesheet
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    protected function canStop(Timesheet $timesheet, User $user, TokenInterface $token)
    {
        // if a teamlead stops an entry for another user, check that this user is part of his team
        return $this->isOwnOrTeamlead($timesheet, $user, $token);
    }

    /**
     * @param Activity $activity
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    protected function canStart(Activity $activity, User $user, TokenInterface $token)
    {
        // we could check the amount of active entries
        // if a teamlead starts an entry for another user, check that this user is part of his team

        if (!$activity->getVisible()) {
            return false;
        }
        if (!$activity->getProject()->getVisible()) {
            return false;
        }
        if (!$activity->getProject()->getCustomer()->getVisible()) {
            return false;
        }

        return true;
    }

    /**
     * @param Timesheet $timesheet
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    protected function canView(Timesheet $timesheet, User $user, TokenInterface $token)
    {
        return $this->isOwnOrTeamlead($timesheet, $user, $token);
    }

    /**
     * @param Timesheet $timesheet
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    protected function canEdit(Timesheet $timesheet, User $user, TokenInterface $token)
    {
        return $this->isOwnOrTeamlead($timesheet, $user, $token);
    }

    /**
     * @param TokenInterface $token
     * @return bool
     */
    protected function canDelete(Timesheet $timesheet, User $user, TokenInterface $token)
    {
        if (!$this->isFullyAuthenticated($token)) {
            return false;
        }

        return $this->isOwnOrAdmin($timesheet, $user, $token);
    }

    /**
     * @param TokenInterface $token
     * @return bool
     */
    protected function isOwnOrTeamlead(Timesheet $timesheet, User $user, TokenInterface $token)
    {
        if ($timesheet->getUser()->getId() == $user->getId()) {
            return true;
        }

        return $this->hasRole('ROLE_TEAMLEAD', $token);
    }

    /**
     * @param TokenInterface $token
     * @return bool
     */
    protected function isOwnOrAdmin(Timesheet $timesheet, User $user, TokenInterface $token)
    {
        if ($timesheet->getUser()->getId() == $user->getId()) {
            return true;
        }

        return $this->hasRole('ROLE_ADMIN', $token);
    }
}
