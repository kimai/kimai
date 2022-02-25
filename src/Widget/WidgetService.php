<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget;

/**
 * @final
 */
class WidgetService
{
    /**
     * @var array
     */
    private $widgets = [];

    public function hasWidget(string $id): bool
    {
        return \array_key_exists($id, $this->widgets);
    }

    public function registerWidget(WidgetInterface $widget): void
    {
        if (!empty($widget->getId())) {
            $this->widgets[$widget->getId()] = $widget;
        }
    }

    /**
     * @return array<string, WidgetInterface>
     */
    public function getAllWidgets(): array
    {
        return $this->widgets;
    }

    public function getWidget(string $id): WidgetInterface
    {
        if (!$this->hasWidget($id)) {
            throw new \InvalidArgumentException(sprintf('Cannot find widget: %s', $id));
        }

        return $this->widgets[$id];
    }
}
