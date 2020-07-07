<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting;

final class MonthlyUserList
{
    /**
     * @var \DateTime
     */
    private $date;

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): MonthlyUserList
    {
        $this->date = $date;

        return $this;
    }
}
