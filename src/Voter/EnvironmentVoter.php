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
 * A voter to check permissions that might rely on external resources,
 * like environment variables or PHP ini settings.
 *
 * @extends Voter<string, Invoice>
 */
final class EnvironmentVoter extends Voter
{
    /**
     * support rules based on the given invoice
     */
    private const ALLOWED_ATTRIBUTES = [
        'upload_invoice_template',
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
        return $subjectType === 'null';
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject === null && $this->supportsAttribute($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // make sure the requested permission is allowed for the actual user
        if (!$this->rolePermissionManager->hasRolePermission($user, $attribute)) {
            return false;
        }

        if ($attribute === 'upload_invoice_template') {
            return filter_var(\ini_get('file_uploads'), FILTER_VALIDATE_BOOLEAN);
        }

        return false;
    }
}
