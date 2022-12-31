<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\Activity;
use App\Model\Statistic\BudgetStatistic;

class ActivityStatistic extends BudgetStatistic implements \JsonSerializable
{
    /**
     * @var Activity
     */
    private $activity;

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(Activity $activity): void
    {
        $this->activity = $activity;
    }

    /**
     * Added for simpler re-use in frontend (charts).
     *
     * @return string|null
     */
    public function getColor(): ?string
    {
        if ($this->activity === null) {
            return null;
        }

        return $this->activity->getColor();
    }

    /**
     * Added for simpler re-use in frontend (charts).
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        if ($this->activity === null) {
            return null;
        }

        return $this->activity->getName();
    }

    public function jsonSerialize(): mixed
    {
        return array_merge(parent::jsonSerialize(), [
            'name' => $this->getName(),
            'color' => $this->getColor(),
        ]);
    }
}
