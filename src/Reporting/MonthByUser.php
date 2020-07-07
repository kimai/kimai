<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting;

use App\Entity\User;

final class MonthByUser
{
    /**
     * @var User
     */
    private $user;
    /**
     * @var \DateTime
     */
    private $date;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): MonthByUser
    {
        $this->user = $user;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): MonthByUser
    {
        $this->date = $date;

        return $this;
    }
}
