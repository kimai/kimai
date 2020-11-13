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
}
