<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

class DashboardSection
{
    /**
     * @var null|string
     */
    protected $title;
    /**
     * @var Widget[]
     */
    protected $widgets = [];
    /**
     * @var int
     */
    protected $order = 0;

    /**
     * @param null|string $title
     */
    public function __construct(?string $title)
    {
        $this->title = $title;
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @param int $order
     * @return DashboardSection
     */
    public function setOrder(int $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return Widget[]
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }

    /**
     * @param Widget $widget
     * @return DashboardSection
     */
    public function addWidget(Widget $widget)
    {
        $this->widgets[] = $widget;

        return $this;
    }
}
