<?php

/*
 * This file is part of the Kimai CustomReport plugin.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting;

final class WeeklyUserList
{
    /**
     * @var \DateTime
     */
    private $date;

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): WeeklyUserList
    {
        $this->date = $date;

        return $this;
    }
}
