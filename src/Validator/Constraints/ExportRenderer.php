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
final class ExportRenderer extends Constraint
{
    public const UNKNOWN_TYPE = 'kimai-export-type-00';

    protected const ERROR_NAMES = [
        self::UNKNOWN_TYPE => 'Unknown exporter type.',
    ];

    public string $message = 'Unknown exporter type.';
}
