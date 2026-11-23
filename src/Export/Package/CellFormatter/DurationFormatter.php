<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package\CellFormatter;

final class DurationFormatter implements CellFormatterInterface, CellWithFormatInterface
{
    public function __construct(private readonly string $format)
    {
    }

    public function formatValue(mixed $value): mixed
    {
        if (!is_numeric($value) || (int) $value === 0) {
            return new \DateInterval('PT0S');
        }

        $value = (int) $value;

        $seconds = abs($value);
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);
        $seconds %= 60;

        $intervalSpec = \sprintf('PT%dH%dM%dS', $hours, $minutes, $seconds);
        $interval = new \DateInterval($intervalSpec);

        if ($value < 0) {
            $interval->invert = 1;
        }

        return $interval;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
