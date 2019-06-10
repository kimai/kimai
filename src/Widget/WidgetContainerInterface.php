<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget;

interface WidgetContainerInterface extends WidgetInterface
{
    public function getOrder(): int;

    public function setOrder(int $order);

    /**
     * @return WidgetInterface[]
     */
    public function getWidgets(): array;

    public function addWidget(WidgetInterface $widget);
}
