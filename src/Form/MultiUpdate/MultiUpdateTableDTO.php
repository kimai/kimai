<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\MultiUpdate;

class MultiUpdateTableDTO
{
    /**
     * @var object[]
     */
    private $entities = [];
    /**
     * @var string[]
     */
    private $actions = ['' => ''];
    /**
     * @var string
     */
    private $action = null;

    /**
     * @return object[]
     */
    public function getEntities(): iterable
    {
        return $this->entities;
    }

    /**
     * @param object[] $entities
     * @return MultiUpdateTableDTO
     */
    public function setEntities(iterable $entities): MultiUpdateTableDTO
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

    public function addAction(string $label, string $url): MultiUpdateTableDTO
    {
        $this->actions[$label] = $url;

        return $this;
    }

    public function addDelete(string $url): MultiUpdateTableDTO
    {
        $this->actions['action.delete'] = $url;

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
