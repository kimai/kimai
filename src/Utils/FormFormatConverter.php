<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

class FormFormatConverter
{
    /**
     * This defines the mapping between ICU date format and Symfony Form/Date format.
     *
     * @var array
     */
    private static $formatConvertRules = [
        // Litepicker interprets a year like 22 as 1922 instead of 2022
        // so we have to make sure that it is always a4-digit year
        'yy' => 'yyyy', 'y' => 'yyyy',
    ];

    public function convert(string $format): string
    {
        return strtr($format, self::$formatConvertRules);
    }
}
