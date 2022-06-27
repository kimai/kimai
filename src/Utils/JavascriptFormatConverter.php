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
     * Returns associated moment.js format.
     *
     * @param string $format
     * @return string
     */
    public function convert(string $format): string
    {
        return strtr($format, self::$formatConvertRules);
    }
}
