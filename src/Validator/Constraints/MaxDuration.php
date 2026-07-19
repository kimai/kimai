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
final class MaxDuration extends Constraint
{
    public const MAX_DURATION_ERROR = '8b2f4a1c-7d3e-4f6b-9a05-1c8e2d7b3f49';

    protected const ERROR_NAMES = [
        self::MAX_DURATION_ERROR => 'MAX_DURATION_ERROR',
    ];

    public string $message = 'A maximum duration of {{ value }} is allowed.';

    /**
     * @param int $value the maximum allowed duration in seconds
     * @param array<string>|null $groups
     */
    public function __construct(
        public int $value,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }
}
