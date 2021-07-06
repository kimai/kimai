<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model\Statistic;

use App\Entity\User;

final class UserYear
{
    /**
     * @var Year
     */
    private $year;
    /**
     * @var User
     */
    private $user;

    public function __construct(User $user, Year $year)
    {
        $this->user = $user;
        $this->year = $year;
    }

    public function getYear(): Year
    {
        return $this->year;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDuration(): int
    {
        $duration = 0;
        foreach ($this->year->getMonths() as $month) {
            $duration += $month->getDuration();
        }

        return $duration;
    }

    public function getRate(): float
    {
        $rate = 0;
        foreach ($this->year->getMonths() as $month) {
            $rate += $month->getRate();
        }

        return $rate;
    }
}
