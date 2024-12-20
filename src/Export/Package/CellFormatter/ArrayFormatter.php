<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package\CellFormatter;

final class ArrayFormatter implements CellFormatterInterface
{
    public function formatValue(mixed $value): mixed
    {
        if (!\is_array($value)) {
            throw new \InvalidArgumentException('Only arrays are supported');
        }

        return implode(', ', $value);
    }
}
