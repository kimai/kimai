<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

final class StringHelper
{
    // @see https://github.com/payloadbox/csv-injection-payloads
    private const DDE_PAYLOADS = ['=', '-', '@', '+', "\t", "\n", "\r", "\r\n"];

    public static function ensureMaxLength(?string $string, int $length): ?string
    {
        if (null === $string) {
            return null;
        }

        if (mb_strlen($string) > $length) {
            $string = mb_substr($string, 0, $length);
        }

        return $string;
    }

    public static function sanitizeDDE(string $text): string
    {
        // see #3189
        if (\strlen($text) === 0) {
            return $text;
        }

        $sanitize = false;

        if (\in_array($text[0], self::DDE_PAYLOADS)) {
            $sanitize = true;
        } elseif (stripos($text, 'DDE') !== false) {
            $sanitize = true;
        }

        if ($sanitize) {
            // trying to prevent fucking Microsoft "feature" DDE
            $text = "' " . $text;
        }

        return $text;
    }
}
