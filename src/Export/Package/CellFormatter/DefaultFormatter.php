<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package\CellFormatter;

final class DefaultFormatter implements CellFormatterInterface
{
    public function formatValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!\is_scalar($value)) {
            throw new \InvalidArgumentException('Only scalar values are supported');
        }

        return $value;
    }
}
