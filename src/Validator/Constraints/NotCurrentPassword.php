<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Prevents that a user sets the current password as new password.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class NotCurrentPassword extends Constraint
{
    public const SAME_AS_CURRENT_PASSWORD = 'kimai-password-01';

    protected const ERROR_NAMES = [
        self::SAME_AS_CURRENT_PASSWORD => 'SAME_AS_CURRENT_PASSWORD',
    ];

    public string $message = 'The new password must be different from the current password.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
