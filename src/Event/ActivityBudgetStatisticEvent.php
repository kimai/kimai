<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Model\ActivityBudgetStatisticModel;

final class ActivityBudgetStatisticEvent
{
    private $models;
    private $begin;
    private $end;

    /**
     * @param ActivityBudgetStatisticModel[] $models
     * @param \DateTime|null $begin
     * @param \DateTime|null $end
     */
    public function __construct(array $models, ?\DateTime $begin = null, ?\DateTime $end = null)
    {
        $this->models = $models;
        $this->begin = $begin;
        $this->end = $end;
    }

    public function getModel(int $activityId): ?ActivityBudgetStatisticModel
    {
        if (isset($this->models[$activityId])) {
            return $this->models[$activityId];
        }

        foreach ($this->models as $model) {
            if ($model->getActivity()->getId() === $activityId) {
                return $model;
            }
        }

        return null;
    }

    /**
     * @return ActivityBudgetStatisticModel[]
     */
    public function getModels(): array
    {
        return $this->models;
    }

    public function getBegin(): ?\DateTime
    {
        return $this->begin;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }
}
