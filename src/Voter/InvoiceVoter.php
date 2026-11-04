<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Voter;

use App\Entity\Invoice;
use App\Entity\User;
use App\Security\RolePermissionManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * A voter to check permissions on Invoice.
 *
 * @extends Voter<string, Invoice>
 */
final class InvoiceVoter extends Voter
{
    /**
     * support rules based on the given invoice
     */
    private const ALLOWED_ATTRIBUTES = [
        'view_invoice',
        'edit_invoice',
        'delete_invoice',
    ];

    public function __construct(private readonly RolePermissionManager $rolePermissionManager)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return \in_array($attribute, self::ALLOWED_ATTRIBUTES, true);
    }

    public function supportsType(string $subjectType): bool
    {
        return str_contains($subjectType, Invoice::class);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Invoice && $this->supportsAttribute($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Invoice) {
            return false;
        }

        // every user needs to be able to view-invoices in order to perform any action
        if (!$this->rolePermissionManager->hasRolePermission($user, 'view_invoice')) {
            return false;
        }

        // this should never happen
        if ($subject->getCustomer() === null) {
            return false;
        }

        // check if the user is allowed to see the invoice customer
        if (!$this->rolePermissionManager->checkTeamAccessCustomer($subject->getCustomer(), $user)) {
            return false;
        }

        // all good here
        if ($attribute === 'view_invoice') {
            return true;
        }

        // there is no edit_invoice, so we only check if the user can create an invoice for the customer
        if ($attribute === 'edit_invoice') {
            return $this->rolePermissionManager->hasRolePermission($user, 'create_invoice');
        }

        if ($attribute === 'delete_invoice') {
            return $this->rolePermissionManager->hasRolePermission($user, 'delete_invoice');
        }

        return false;
    }
}
