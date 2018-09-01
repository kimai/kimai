<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

class Widget
{
    public const TYPE_COUNTER = 'counter';
    public const TYPE_MORE = 'more';

    public const DATA_TYPE_INT = 'int';
    public const DATA_TYPE_MONEY = 'money';
    public const DATA_TYPE_DURATION = 'duration';

    /**
     * @var string|null
     */
    protected $title;
    /**
     * @var string
     */
    protected $icon = '';
    /**
     * @var string
     */
    protected $color = '';
    /**
     * @var string
     */
    protected $type = self::TYPE_COUNTER;
    /**
     * @var string
     */
    protected $range;
    /**
     * @var string|null
     */
    protected $route;
    /**
     * @var array
     */
    protected $routeOptions = [];
    /**
     * @var mixed
     */
    protected $data;
    /**
     * @var string
     */
    protected $dataType = self::DATA_TYPE_INT;

    /**
     * @param string $title
     * @param mixed $data
     */
    public function __construct(string $title, $data)
    {
        $this->title = $title;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * @param string $dataType
     * @return Widget
     */
    public function setDataType(string $dataType)
    {
        $this->dataType = $dataType;

        return $this;
    }

    /**
     * @return array
     */
    public function getRouteOptions(): array
    {
        return $this->routeOptions;
    }

    /**
     * @param array $routeOptions
     * @return Widget
     */
    public function setRouteOptions(array $routeOptions)
    {
        $this->routeOptions = $routeOptions;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRoute(): ?string
    {
        return $this->route;
    }

    /**
     * @param string $route
     * @return Widget
     */
    public function setRoute(string $route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     * @return Widget
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Widget
     */
    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     * @return Widget
     */
    public function setIcon(string $icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @param string $color
     * @return Widget
     */
    public function setColor(string $color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Widget
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }
}
