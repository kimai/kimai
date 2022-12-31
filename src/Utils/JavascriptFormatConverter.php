<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

final class JavascriptFormatConverter
{
    /**
     * Convert PHP date format to Luxon compatible format.
     *
     * @see https://moment.github.io/luxon/#/formatting?id=table-of-tokens
     * @see https://www.php.net/manual/en/datetime.format.php
     * @var array
     */
    private static array $formatConvertRules = [
        // year: Litepicker interprets 2-digit year as 1900, so we have to convert 20 to 2022.
        'yyyy' => 'YYYY', 'yy' => 'YYYY', 'y' => 'YYYY',
        // day
        'dd' => 'DD', 'd' => 'D',
        // day of week
        'EE' => 'ddd', 'EEEEEE' => 'dd',
        // timezone
        'ZZZZZ' => 'Z', 'ZZZ' => 'ZZ',
        // letter 'T'
        '\'T\'' => 'T',
        // am/pm (a) to AM/PM (A) - Luxon always produces uppercase AM/PM
        'a' => 'A',
    ];

    /**
     * The output of this format is used only to convert the Litepicker date object
     * to the input field (expected by Symfony form).
     */
    public function convert(string $format): string
    {
        return strtr($format, self::$formatConvertRules);
    }
}
