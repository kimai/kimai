<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Voter;

use AppBundle\Entity\User;
use AppBundle\Voter\AbstractVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use TimesheetBundle\Entity\Project;

/**
 * A voter to check permissions on Projects.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProjectVoter extends AbstractVoter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, array(self::VIEW, self::EDIT, self::DELETE))) {
            return false;
        }

        if (!$subject instanceof Project) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param Project $subject
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
            case self::VIEW:
                return $this->canView($subject, $user, $token);
            case self::EDIT:
                return $this->canEdit($subject, $user, $token);
            case self::DELETE:
                return $this->canDelete($token);
        }

        return false;
    }

    /**
     * @param Project $project
     * @param User $user
     * @return bool
     */
    protected function canView(Project $project, User $user, TokenInterface $token)
    {
        if ($this->canEdit($project, $user, $token)) {
            return true;
        }

        return false;
    }

    /**
     * @param Project $project
     * @param User $user
     * @return bool
     */
    protected function canEdit(Project $project, User $user, TokenInterface $token)
    {
        if ($this->canDelete($token)) {
            return true;
        }

        return false;
    }

    /**
     * @param TokenInterface $token
     * @return bool
     */
    protected function canDelete(TokenInterface $token)
    {
        return $this->hasRole('ROLE_ADMIN', $token);
    }
}
