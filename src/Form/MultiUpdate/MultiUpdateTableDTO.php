<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\MultiUpdate;

use Doctrine\Common\Collections\Collection;

class MultiUpdateTableDTO
{
    /**
     * @var array<object>|Collection<object>
     */
    private array|Collection $entities = [];
    /**
     * @var string[]
     */
    private array $actions = ['' => ''];
    private ?string $action = null;

    /**
     * @return object[]
     */
    public function getEntities(): array|Collection
    {
        return $this->entities;
    }

    /**
     * @param array<object>|Collection<object> $entities
     * @return MultiUpdateTableDTO
     */
    public function setEntities(array|Collection $entities): MultiUpdateTableDTO
    {
        $this->entities = $entities;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    public function hasAction(): bool
    {
        return \count($this->actions) > 1;
    }

    public function addAction(string $label, string $url): MultiUpdateTableDTO
    {
        $this->actions[$label] = $url;

        return $this;
    }

    public function addDelete(string $url): MultiUpdateTableDTO
    {
        $this->actions['delete'] = $url;

        return $this;
    }

    public function addUpdate(string $url): MultiUpdateTableDTO
    {
        $this->actions['action.edit'] = $url;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): MultiUpdateTableDTO
    {
        $this->action = $action;

        return $this;
    }
}
