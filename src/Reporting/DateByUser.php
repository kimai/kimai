<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting;

use App\Entity\User;

abstract class DateByUser
{
    /**
     * @var User
     */
    private $user;
    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $sumType;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getSumType(): ?string
    {
        return $this->sumType;
    }

    public function setSumType(string $sumType): self
    {
        $this->sumType = $sumType;

        return $this;
    }
}
