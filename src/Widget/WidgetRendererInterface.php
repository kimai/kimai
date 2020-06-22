<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget;

interface WidgetRendererInterface
{
    /**
     * Checks if the given widget can be rendered.
     *
     * Most renderer check something like:
     * return $widget instanceof MyWidgetType;
     *
     * @param WidgetInterface $widget
     * @return bool
     */
    public function supports(WidgetInterface $widget): bool;

    /**
     * Renders the given widget.
     *
     * The given $options array overwrites the widgets internal options for this call.
     *
     * @param WidgetInterface $widget
     * @param array $options
     * @return string
     */
    public function render(WidgetInterface $widget, array $options = []): string;
}
