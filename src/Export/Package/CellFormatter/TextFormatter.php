<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package\CellFormatter;

use App\Utils\StringHelper;

final class TextFormatter implements CellFormatterInterface
{
    public function __construct(private readonly bool $sanitizeDde)
    {
    }

    public function formatValue(mixed $value): mixed
    {
        if ($this->sanitizeDde && \is_string($value)) {
            $value = StringHelper::sanitizeDDE($value);
        }

        return $value;
    }
}
