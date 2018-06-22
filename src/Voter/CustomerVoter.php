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
use App\Entity\Customer;

/**
 * A voter to check permissions on Customers.
 */
class CustomerVoter extends AbstractVoter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    public const ALLOWED_ATTRIBUTES = [
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

        if (!$subject instanceof Customer) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param Customer $subject
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
     * @param Customer $customer
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    protected function canView(Customer $customer, User $user, TokenInterface $token)
    {
        if ($this->canEdit($customer, $user, $token)) {
            return true;
        }

        return false;
    }

    /**
     * @param Customer $customer
     * @param User $user
     * @param TokenInterface $token
     * @return bool
     */
    protected function canEdit(Customer $customer, User $user, TokenInterface $token)
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
        return $this->isFullyAuthenticated($token) && $this->hasRole('ROLE_ADMIN', $token);
    }
}
