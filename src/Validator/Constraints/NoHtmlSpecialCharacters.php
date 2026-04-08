<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class NoHtmlSpecialCharacters extends Constraint
{
    public const SPECIAL_CHARACTERS_FOUND = 'kimai-html-character-001';

    protected const ERROR_NAMES = [
        self::SPECIAL_CHARACTERS_FOUND => 'These characters are not allowed: {{ chars }}',
    ];

    public string $message = 'These characters are not allowed: {{ chars }}';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
