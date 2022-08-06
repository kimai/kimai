<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Widget\WidgetInterface;

abstract class AbstractWidgetType extends AbstractWidget
{
    private ?string $id = null;
    private string $title = '';
    private int $height = WidgetInterface::HEIGHT_SMALL;
    private int $width = WidgetInterface::WIDTH_SMALL;
    /**
     * @var array<string>
     */
    private array $permissions = [];

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): AbstractWidgetType
    {
        $this->height = $height;

        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): AbstractWidgetType
    {
        $this->width = $width;

        return $this;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        if (!empty($this->id)) {
            return $this->id;
        }

        return (new \ReflectionClass($this))->getShortName();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setOptions(array $options): self
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }

        return $this;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): AbstractWidgetType
    {
        $this->permissions = $permissions;

        return $this;
    }
}
