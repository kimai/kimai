<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package\CellFormatter;

use App\Utils\Duration;

final class DurationDecimalFormatter implements CellFormatterInterface
{
    private readonly Duration $duration;

    public function __construct()
    {
        $this->duration = new Duration();
    }

    public function formatValue(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return $this->duration->formatDecimal((int) $value);
        }

        return 0.0;
    }
}
