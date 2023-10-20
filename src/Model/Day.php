<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use DateTimeImmutable;

class Day
{
    public function __construct(private DateTimeImmutable $day)
    {
    }

    public function getDay(): DateTimeImmutable
    {
        return $this->day;
    }
}
