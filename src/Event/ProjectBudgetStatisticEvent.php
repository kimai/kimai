<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Model\ProjectBudgetStatisticModel;

final class ProjectBudgetStatisticEvent
{
    /**
     * @param ProjectBudgetStatisticModel[] $models
     */
    public function __construct(
        private readonly array $models,
        private readonly ?\DateTimeInterface $begin = null,
        private readonly ?\DateTimeInterface $end = null
    )
    {
    }

    public function getModel(int $projectId): ?ProjectBudgetStatisticModel
    {
        if (isset($this->models[$projectId])) {
            return $this->models[$projectId];
        }

        foreach ($this->models as $model) {
            if ($model->getProject()->getId() === $projectId) {
                return $model;
            }
        }

        return null;
    }

    /**
     * @return ProjectBudgetStatisticModel[]
     */
    public function getModels(): array
    {
        return $this->models;
    }

    public function getBegin(): ?\DateTimeInterface
    {
        return $this->begin;
    }

    public function getEnd(): ?\DateTimeInterface
    {
        return $this->end;
    }
}
