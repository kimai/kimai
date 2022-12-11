<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

final class FormFormatConverter
{
    public const PATTERN_DAY_SINGLE = '(([1-9])|([1-2][0-9])|(3[01]))';
    public const PATTERN_DAY_DOUBLE = '((0[1-9])|([1-2][0-9])|(3[01]))';
    public const PATTERN_MONTH_SINGLE = '(([1-9])|([1][0-2]))';
    public const PATTERN_MONTH_DOUBLE = '((0[1-9])|([1][0-2]))';
    public const PATTERN_YEAR = '(19|20)\d{2}';
    public const PATTERN_HOUR_SINGLE = '([0-9]|[1][0-9]|2[0-3])';
    public const PATTERN_HOUR_DOUBLE = '([0-9]|[01][0-9]|2[0-3])';
    public const PATTERN_MINUTES = '([0-5][0-9])';

    /**
     * This defines the mapping between ICU date format and PHP Date format.
     *
     * @see https://www.php.net/manual/en/datetime.format.php
     * @var array
     */
    private static array $formatConvertRules = [
        // Litepicker interprets a year like 22 as 1922 instead of 2022
        // so we have to make sure that it is always a4-digit year
        "'h'" => "\h",  // special format for fr_CA which includes 'h' as character
        'yy' => 'yyyy',
        'y' => 'yyyy',
        'mm' => 'i',    // ICU 2 letter minutes
        'a' => 'A',     // uppercase AM/PM, Luxon only supports uppercase
        'HH' => 'H',    // H = 24-hour format of an hour with leading zeros
        'h' => 'g',     // g = 12-hour format of an hour without leading zeros	1 through 12
        'H' => 'G',     // G = 24-hour format of an hour without leading zeros	0 through 23
                        // h = 12-hour format of an hour with leading zeros	01 through 12
    ];

    public function convert(string $format): string
    {
        return strtr($format, self::$formatConvertRules);
    }

    /**
     * This works with ICU and DateTime format.
     *
     * @param string $format
     * @param bool $html
     * @return string
     */
    public function convertToPattern(string $format, bool $html = true): string
    {
        if (!$html) {
            $format = preg_quote($format, '/');
        }

        $pattern = $format;

        // special case fr_CA
        $pattern = str_replace('\\\\h', '*****', $pattern);
        $pattern = str_replace('\\h', '*****', $pattern);
        $pattern = str_replace("'h'", '*****', $pattern);

        // days
        $pattern = str_replace('dd', self::PATTERN_DAY_DOUBLE, $pattern);
        $pattern = str_replace('d', self::PATTERN_DAY_SINGLE, $pattern);
        // months
        $pattern = str_replace('MM', self::PATTERN_MONTH_DOUBLE, $pattern);
        $pattern = str_replace('M', self::PATTERN_MONTH_SINGLE, $pattern);
        // years
        $pattern = str_replace('yyyy', self::PATTERN_YEAR, $pattern);
        $pattern = str_replace('yy', self::PATTERN_YEAR, $pattern);
        $pattern = str_replace('y', self::PATTERN_YEAR, $pattern);
        // time
        $pattern = str_replace('HH', self::PATTERN_HOUR_DOUBLE, $pattern);
        $pattern = str_replace('H', self::PATTERN_HOUR_DOUBLE, $pattern);
        $pattern = str_replace('G', self::PATTERN_HOUR_SINGLE, $pattern);
        $pattern = str_replace('h', self::PATTERN_HOUR_SINGLE, $pattern);
        $pattern = str_replace('g', self::PATTERN_HOUR_SINGLE, $pattern);
        $pattern = str_replace('i', self::PATTERN_MINUTES, $pattern);
        $pattern = str_replace('mm', self::PATTERN_MINUTES, $pattern);
        $pattern = str_replace('A', '(AM|PM){1}', $pattern);
        $pattern = str_replace('a', '(AM|PM){1}', $pattern);
        $pattern = str_replace('*****', 'h', $pattern);

        if (!$html) {
            $pattern = '/^' . $pattern . '$/';
        }

        return $pattern;
    }
}
