<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class ColorChoices extends Constraint
{
    public const COLOR_CHOICES_ERROR = 'ui5hffg-dsfef3-1234-5678-2g8jkfr56d84';
    public const COLOR_CHOICES_NAME_ERROR = 'ui5hffg-dsfef3-1234-5679-2g8jkfr56d84';

    protected const ERROR_NAMES = [
        self::COLOR_CHOICES_ERROR => 'COLOR_CHOICES_ERROR',
        self::COLOR_CHOICES_NAME_ERROR => 'COLOR_CHOICES_NAME_ERROR',
    ];

    public string $message = 'The given value {{ value }} is not a valid hexadecimal color.';
    public string $invalidNameMessage = 'The given value {{ name }} is not a valid color name for {{ color }}. Allowed are {{ max }} alpha-numerical characters, including minus and space.';
    public int $maxLength = 20;
}
