<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Model;

use App\Entity\User;
use App\Model\Year as BaseYear;

/**
 * @method array<Month> getMonths()
 * @method Month getMonth(\DateTimeInterface $month)
 */
final class Year extends BaseYear
{
    public function __construct(\DateTimeInterface $month, private User $user)
    {
        parent::__construct($month);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    protected function createMonth(\DateTimeInterface $month): Month
    {
        return new Month($month);
    }

    public function getExpectedTime(): int
    {
        $time = 0;

        foreach ($this->getMonths() as $month) {
            $time += $month->getExpectedTime();
        }

        return $time;
    }

    public function getActualTime(): int
    {
        $time = 0;

        foreach ($this->getMonths() as $month) {
            $time += $month->getActualTime();
        }

        return $time;
    }
}
