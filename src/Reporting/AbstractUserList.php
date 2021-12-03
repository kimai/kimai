<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Reporting;

abstract class AbstractUserList
{
    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $sumType;

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getSumType(): ?string
    {
        return $this->sumType;
    }

    public function setSumType(string $sumType): void
    {
        $this->sumType = $sumType;
    }
}
