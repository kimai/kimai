<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package\CellFormatter;

final class DateFormatter implements CellFormatterInterface, CellWithFormatInterface
{
    public function __construct(private readonly ?\DateTimeZone $timezone = null)
    {
    }

    public function formatValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            if ($this->timezone !== null) {
                return \DateTime::createFromInterface($value)->setTimezone($this->timezone);
            }

            return $value;
        }

        if ($value === null) {
            return null;
        }

        throw new \InvalidArgumentException('Only DateTimeInterface can be formatted');
    }

    public function getFormat(): string
    {
        return 'yyyy-mm-dd';
    }
}
