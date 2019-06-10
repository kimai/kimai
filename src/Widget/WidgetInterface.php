<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget;

interface WidgetInterface
{
    /**
     * Returns a unique ID for this widget.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Returns the widget title.
     *
     * If no title is necessary, return an empty string.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Returns the widgets datat, to be used in the frontend rendering.
     *
     * @return mixed|null
     */
    public function getData();

    /**
     * Returns a widget option or the $default value if not set.
     *
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getOption(string $name, $default = null);
}
