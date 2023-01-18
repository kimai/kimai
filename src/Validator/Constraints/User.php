<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class User extends Constraint
{
    public const USER_EXISTING_EMAIL = 'kimai-user-00';
    public const USER_EXISTING_NAME = 'kimai-user-01';
    public const USER_EXISTING_EMAIL_AS_NAME = 'kimai-user-02';
    public const USER_EXISTING_NAME_AS_EMAIL = 'kimai-user-03';

    protected const ERROR_NAMES = [
        self::USER_EXISTING_EMAIL => 'The email is already used.',
        self::USER_EXISTING_NAME => 'The username is already used.',
        self::USER_EXISTING_EMAIL_AS_NAME => 'An equal username is already used.',
        self::USER_EXISTING_NAME_AS_EMAIL => 'An equal email is already used.',
    ];

    public string $message = 'The user has invalid settings.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
