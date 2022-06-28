<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

class JavascriptFormatConverter
{
    /**
     * Convert PHP date format to litepicker compatible format.
     *
     * Litepicker interprets 2-digit year as 1900, so we have to convert 20 to 2022.
     *
     * @var array
     */
    private static $formatConvertRules = [
        // year
        'yyyy' => 'YYYY', 'yy' => 'YYYY', 'y' => 'YYYY',
        // day
        'dd' => 'DD', 'd' => 'D',
        // day of week
        'EE' => 'ddd', 'EEEEEE' => 'dd',
        // timezone
        'ZZZZZ' => 'Z', 'ZZZ' => 'ZZ',
        // letter 'T'
        '\'T\'' => 'T',
        // am/pm to AM/PM
        'a' => 'A',
    ];

    /**
     * The output of this format is used only to convert the Litepicker date object
     * to the input field (expected by Symfony form).
     *
     * @param string $format
     * @return string
     */
    public function convert(string $format): string
    {
        return strtr($format, self::$formatConvertRules);
    }
}
