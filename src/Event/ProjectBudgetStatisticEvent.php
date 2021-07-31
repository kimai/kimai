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
    private $models;
    private $begin;
    private $end;

    /**
     * @param ProjectBudgetStatisticModel[] $models
     * @param \DateTime|null $begin
     * @param \DateTime|null $end
     */
    public function __construct(array $models, ?\DateTime $begin = null, ?\DateTime $end = null)
    {
        $this->models = $models;
        $this->begin = $begin;
        $this->end = $end;
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

    public function getBegin(): ?\DateTime
    {
        return $this->begin;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }
}
