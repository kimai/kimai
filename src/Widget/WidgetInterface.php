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
     * Returns the widgets data, to be used in the frontend rendering.
     *
     * @return mixed|null
     */
    public function getData();

    /**
     * Returns all widget options to be used in the frontend.
     */
    public function getOptions(): array;

    /**
     * Sets one widget option, both for internal use and for frontend rendering.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setOption(string $name, $value): void;
}
