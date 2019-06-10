<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Widget\WidgetContainerInterface;
use App\Widget\WidgetInterface;

abstract class AbstractContainer implements WidgetContainerInterface
{
    /**
     * @var string
     */
    protected $title = '';
    /**
     * @var WidgetInterface[]
     */
    protected $widgets = [];
    /**
     * @var int
     */
    protected $order = 0;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed|null
     */
    public function getData()
    {
        return $this->getWidgets();
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getOption(string $name, $default = null)
    {
        return null;
    }

    public function getId(): string
    {
        return $this->title;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order)
    {
        $this->order = $order;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return WidgetInterface[]
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }

    public function addWidget(WidgetInterface $widget)
    {
        $this->widgets[] = $widget;

        return $this;
    }
}
