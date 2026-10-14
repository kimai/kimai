<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Package\CellFormatter;

/**
 * Shared by the export cell formatters that render a DateTimeInterface value into an
 * explicit, caller-supplied render timezone instead of the timestamp's own stored timezone.
 */
trait ConvertsTimezoneTrait
{
    public function __construct(private readonly ?\DateTimeZone $timezone = null)
    {
    }

    private function convertToTimezone(\DateTimeInterface $value): \DateTimeInterface
    {
        if ($this->timezone === null) {
            return $value;
        }

        return \DateTime::createFromInterface($value)->setTimezone($this->timezone);
    }
}
