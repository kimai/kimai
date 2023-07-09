<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\WorkingTime\Model;

use App\Entity\WorkingTime;
use App\Model\Day as BaseDay;

final class Day extends BaseDay
{
    private ?WorkingTime $workingTime = null;
    /** @var array<DayAddon> */
    private array $addons = [];

    public function isLocked(): bool
    {
        if ($this->workingTime !== null && $this->workingTime->isApproved()) {
            return true;
        }

        return false;
    }

    public function getWorkingTime(): ?WorkingTime
    {
        return $this->workingTime;
    }

    public function setWorkingTime(?WorkingTime $workingTime): void
    {
        $this->workingTime = $workingTime;
    }

    /**
     * @return array<DayAddon>
     */
    public function getAddons(): array
    {
        return $this->addons;
    }

    public function hasAddons(): bool
    {
        return \count($this->addons) > 0;
    }

    /**
     * Descriptions show up in the approval PDF and maybe in other places as well.
     */
    public function addAddon(DayAddon $addon): void
    {
        $this->addons[] = $addon;

        if (!$this->isLocked() && $this->workingTime !== null) {
            $this->workingTime->setActualTime($this->workingTime->getActualTime() + $addon->getDuration());
        }
    }
}
