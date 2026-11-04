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
final class NoSpecialCharacters extends Constraint
{
    public const SPECIAL_CHARACTERS_FOUND = 'kimai-html-character-001';

    protected const ERROR_NAMES = [
        self::SPECIAL_CHARACTERS_FOUND => 'These characters are not allowed: {{ chars }}',
    ];

    /** @var string[] */
    public array $characters = [
        '<', // XSS
        '>', // XSS
        '"', // XSS
        '=', // DDE
    ];

    public string $message = 'These characters are not allowed: {{ chars }}';

    /**
     * @param string[]|null $character
     */
    public function __construct(
        mixed $options = null,
        ?string $message = null,
        ?array $character = null,
        ?array $groups = null,
        mixed $payload = null
    )
    {
        parent::__construct($options, $groups, $payload);
        $this->message = $message ?? $this->message;
        $this->characters = $character ?? $this->characters;
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
