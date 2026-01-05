<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Audit;

/**
 * For entity classes, whose changes could be logged.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Loggable
{
    /**
     * @param class-string|null $customFieldClass
     */
    public function __construct(
        public ?string $customFieldClass = null,
        public array $ignoredProperties = [],
        public ?string $title = null,
        public string $translationDomain = 'messages',
    )
    {
    }
}
