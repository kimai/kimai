<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use App\Model\CustomerBudgetStatisticModel;

final class CustomerBudgetStatisticEvent
{
    /**
     * @param CustomerBudgetStatisticModel[] $models
     * @param \DateTime|null $begin
     * @param \DateTime|null $end
     */
    public function __construct(private array $models, private ?\DateTime $begin = null, private ?\DateTime $end = null)
    {
    }

    public function getModel(int $customerId): ?CustomerBudgetStatisticModel
    {
        if (isset($this->models[$customerId])) {
            return $this->models[$customerId];
        }

        foreach ($this->models as $model) {
            if ($model->getCustomer()->getId() === $customerId) {
                return $model;
            }
        }

        return null;
    }

    /**
     * @return CustomerBudgetStatisticModel[]
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
