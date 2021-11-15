<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

class DateFormatConverter
{
    /**
     * This defines the mapping between PHP date format (key) and ICU date format (value).
     * https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
     *
     * @var array
     */
    private static $formatConvertRules = [
        // hours
        'h' => 'hh', 'H' => 'HH',
        // minutes
        'i' => 'mm',
        // am/pm to AM/PM
        'A' => 'a'
    ];

    public function convert(string $format): string
    {
        return strtr($format, self::$formatConvertRules);
    }
}
