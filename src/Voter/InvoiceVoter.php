<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\Activity;
use App\Entity\InvoiceTemplate;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * A voter to check permissions on Invoices.
 */
class InvoiceVoter extends AbstractVoter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const CREATE = 'create';
    public const DELETE = 'delete';

    public const ALLOWED_ATTRIBUTES = [
        self::VIEW,
        self::CREATE,
        self::EDIT,
        self::DELETE
    ];

    public const ALLOWED_SUBJECTS = [
        'invoice',
        'invoice_template'
    ];

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!$subject instanceof InvoiceTemplate) {
            if (!in_array($subject, self::ALLOWED_SUBJECTS)) {
                return false;
            }
        }

        if (!in_array($attribute, self::ALLOWED_ATTRIBUTES)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param Activity $subject
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
                return $this->canView($user, $token);
            case self::CREATE:
                return $this->canCreate($user, $token);
            case self::EDIT:
                return $this->canEdit($user, $token);
            case self::DELETE:
                return $this->canDelete($token);
        }

        return false;
    }

    /**
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    protected function canView(User $user, TokenInterface $token)
    {
        if ($this->canEdit($user, $token)) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    protected function canCreate(User $user, TokenInterface $token)
    {
        if ($this->canDelete($token)) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    protected function canEdit(User $user, TokenInterface $token)
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
        return $this->isFullyAuthenticated($token) && $this->hasRole('ROLE_TEAMLEAD', $token);
    }
}
