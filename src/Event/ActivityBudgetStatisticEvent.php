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
    private readonly ?\DateTime $begin;
    private readonly ?\DateTime $end;

    /**
     * @param ActivityBudgetStatisticModel[] $models
     */
    public function __construct(
        private readonly array $models,
        ?\DateTimeInterface $begin = null,
        ?\DateTimeInterface $end = null
    )
    {
        if ($begin !== null) {
            $begin = \DateTime::createFromInterface($begin);
        }
        $this->begin = $begin;

        if ($end !== null) {
            $end = \DateTime::createFromInterface($end);
        }
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
