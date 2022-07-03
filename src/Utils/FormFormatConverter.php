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

    public function convertToPattern(string $format, bool $html = false): string
    {
        $pattern = preg_quote($format, '/');

        $pattern = str_replace('\\\\h', '*****', $pattern);
        // days
        $pattern = str_replace('dd', '[0-9]{2}', $pattern);
        $pattern = str_replace('d', '[0-9]{1,2}', $pattern);
        // months
        $pattern = str_replace('MM', '[0-9]{2}', $pattern);
        $pattern = str_replace('M', '[0-9]{1,2}', $pattern);
        // years
        $pattern = str_replace('yyyy', '[0-9]{4}', $pattern);
        $pattern = str_replace('yy', '[0-9]{2}', $pattern);
        $pattern = str_replace('y', '[0-9]{2}', $pattern);
        // hours - TODO allow 0-1-2 in first pattern
        $pattern = str_replace('HH', '[0-9]{2}', $pattern);
        $pattern = str_replace('H', '[0-9]{1,2}', $pattern);
        $pattern = str_replace('h', '[0-9]{1,2}', $pattern);
        $pattern = str_replace('i', '[0-9]{1,2}', $pattern);
        $pattern = str_replace('A', '[AM|PM]', $pattern);
        $pattern = str_replace('a', '[am|pm]', $pattern);
        $pattern = str_replace('*****', 'h', $pattern);

        if ($html) {
            return $pattern;
        }

        return '/^' . $pattern . '$/';
    }
}
