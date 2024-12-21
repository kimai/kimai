<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package\CellFormatter;

final class RateFormatter implements CellFormatterInterface
{
    public function formatValue(mixed $value): mixed
    {
        if (\is_float($value)) {
            return (float) number_format($value, 2, '.', '');
        }

        return 0.0;
    }
}
